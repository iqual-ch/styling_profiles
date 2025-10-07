<?php

namespace Drupal\styling_profiles\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetResolver as CoreAssetResolver;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Asset\LibraryDependencyResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\styling_profiles\Service\RuleHandlerManagerInterface;
use Drupal\Core\Asset\CssCollectionOptimizerInterface;

/**
 * Custom Asset Resolver class dependent on the styling profile.
 */
class AssetResolver extends CoreAssetResolver {

  /**
   * The styling profile rule handler manager.
   *
   * @var \Drupal\styling_profiles\Service\RuleHandlerManagerInterface
   */
  protected $styleProfileRuleHandlerManager;

  /**
   * The CSS collection optimizer.
   *
   * @var \Drupal\Core\Asset\CssCollectionOptimizerInterface
   */
  protected $cssCollectionOptimizer;

  /**
   * Constructs a new AssetResolver instance.
   *
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Asset\LibraryDependencyResolverInterface $library_dependency_resolver
   *   The library dependency resolver.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler service.
   * @param \Drupal\styling_profiles\Service\RuleHandlerManagerInterface $style_profile_rule_handler_manager
   *   The styling profile rule handler manager.
   * @param \Drupal\Core\Asset\CssCollectionOptimizerInterface $css_collection_optimizer
   *   The CSS collection optimizer.
   */
  public function __construct(LibraryDiscoveryInterface $library_discovery, LibraryDependencyResolverInterface $library_dependency_resolver, ModuleHandlerInterface $module_handler, ThemeManagerInterface $theme_manager, LanguageManagerInterface $language_manager, CacheBackendInterface $cache, ThemeHandlerInterface $theme_handler, RuleHandlerManagerInterface $style_profile_rule_handler_manager, CssCollectionOptimizerInterface $css_collection_optimizer) {
    parent::__construct($library_discovery, $library_dependency_resolver, $module_handler, $theme_manager, $language_manager, $cache, $theme_handler);
    $this->styleProfileRuleHandlerManager = $style_profile_rule_handler_manager;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public function getCssAssets(AttachedAssetsInterface $assets, $optimize, ?LanguageInterface $language = NULL) {
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
