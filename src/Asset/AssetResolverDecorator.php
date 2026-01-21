<?php

namespace Drupal\styling_profiles\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetResolver;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Asset\CssCollectionOptimizerLazy;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\styling_profiles\Service\RuleHandlerManager;

/**
 * Custom Asset Resolver decorator dependent on the styling profile.
 */
class AssetResolverDecorator extends AssetResolver {

  /**
   * Constructs a new AssetResolverDecorator instance.
   *
   * @param \Drupal\Core\Asset\AssetResolverInterface $innerAssetResolver
   *   The decorated asset resolver service.
   * @param \Drupal\styling_profiles\Service\RuleHandlerManager $styleProfileRuleHandlerManager
   *   The styling profile rule handler manager.
   * @param \Drupal\Core\Asset\CssCollectionOptimizerLazy $cssCollectionOptimizer
   *   The CSS collection optimizer.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   The admin context service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $library_dependency_resolver
   *   The library dependency resolver service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface|null $theme_handler
   *   The theme handler.
   */
  public function __construct(
    protected AssetResolverInterface $innerAssetResolver,
    protected RuleHandlerManager $styleProfileRuleHandlerManager,
    protected CssCollectionOptimizerLazy $cssCollectionOptimizer,
    protected RouteMatchInterface $routeMatch,
    protected AdminContext $adminContext,
    LibraryDiscoveryInterface $library_discovery,
    LibraryDependencyResolverInterface $library_dependency_resolver,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache,
    ?ThemeHandlerInterface $theme_handler = NULL,
  ) {
    parent::__construct(
      $library_discovery,
      $library_dependency_resolver,
      $module_handler,
      $theme_manager,
      $language_manager,
      $cache,
      $theme_handler
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize, ?LanguageInterface $language = NULL) {
    // Check if we're on an admin route and bypass custom logic.
    $route = $this->routeMatch->getRouteObject();
    if ($route && $this->adminContext->isAdminRoute($route)) {
      // Use parent implementation for admin routes.
      return parent::getCssAssets($assets, $optimize, $language);
    }

    if (!$assets->getLibraries()) {
      return [];
    }
    $libraries_to_load = $this->getLibrariesToLoad($assets);
    foreach ($libraries_to_load as $key => $library) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      if (empty($definition['css'])) {
        unset($libraries_to_load[$key]);
      }
    }
    $libraries_to_load = array_values($libraries_to_load);
    if (!$libraries_to_load) {
      return [];
    }
    if (!isset($language)) {
      $language = $this->languageManager->getCurrentLanguage();
    }

    $theme_info = $this->themeManager->getActiveTheme();
    $stylingProfile = $this->styleProfileRuleHandlerManager->getStylingProfile();
    // Add the styling profile to the cache key.
    $cid = 'css:' . $theme_info->getName() . ':' . $stylingProfile . ':' . $language->getId() . Crypt::hashBase64(serialize($libraries_to_load)) . (int) $optimize;
    if ($cached = $this->cache->get($cid)) {
      return $cached->data;
    }

    $css = [];
    $default_options = [
      'type' => 'file',
      'group' => CSS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'media' => 'all',
      'preprocess' => TRUE,
    ];

    foreach ($libraries_to_load as $library) {
      [$extension, $name] = explode('/', $library, 2);
      $definition = $this->libraryDiscovery->getLibraryByName($extension, $name);
      foreach ($definition['css'] as $options) {
        $options += $default_options;
        // Copy the asset library license information to each file.
        $options['license'] = $definition['license'];

        // Files with a query string cannot be preprocessed.
        if ($options['type'] === 'file' && $options['preprocess'] && str_contains($options['data'], '?')) {
          $options['preprocess'] = FALSE;
        }

        // Always add a tiny value to the weight, to conserve the insertion
        // order.
        $options['weight'] += count($css) / 30000;

        // CSS files are being keyed by the full path.
        $css[$options['data']] = $options;
      }
    }

    // Allow modules and themes to alter the CSS assets.
    $this->moduleHandler->alter('css', $css, $assets, $language);
    $this->themeManager->alter('css', $css, $assets, $language);

    if (!empty($css)) {
      // Sort CSS items, so that they appear in the correct order.
      uasort($css, [static::class, 'sort']);

      if ($optimize) {
        $css = $this->cssCollectionOptimizer->optimize($css, array_values($libraries_to_load), $language);
      }
    }
    $this->cache->set($cid, $css, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $css;
  }

}
