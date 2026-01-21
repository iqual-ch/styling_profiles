<?php

namespace Drupal\styling_profiles\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Asset\CssCollectionOptimizerLazy;
use Drupal\styling_profiles\Service\RuleHandlerManager;

/**
 * Custom Asset Resolver decorator dependent on the styling profile.
 */
class AssetResolver implements AssetResolverInterface {

  /**
   * The decorated asset resolver service.
   *
   * @var \Drupal\Core\Asset\AssetResolverInterface
   */
  protected $innerAssetResolver;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

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
   * @param \Drupal\Core\Asset\AssetResolverInterface $inner_asset_resolver
   *   The decorated asset resolver service.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery
   *   The library discovery service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\styling_profiles\Service\RuleHandlerManager $style_profile_rule_handler_manager
   *   The styling profile rule handler manager.
   * @param \Drupal\Core\Asset\CssCollectionOptimizerLazy $css_collection_optimizer
   *   The CSS collection optimizer.
   */
  public function __construct(
    AssetResolverInterface $inner_asset_resolver,
    LibraryDiscoveryInterface $library_discovery,
    ModuleHandlerInterface $module_handler,
    ThemeManagerInterface $theme_manager,
    LanguageManagerInterface $language_manager,
    CacheBackendInterface $cache,
    RuleHandlerManager $style_profile_rule_handler_manager,
    CssCollectionOptimizerLazy $css_collection_optimizer,
  ) {
    $this->innerAssetResolver = $inner_asset_resolver;
    $this->libraryDiscovery = $library_discovery;
    $this->moduleHandler = $module_handler;
    $this->themeManager = $theme_manager;
    $this->languageManager = $language_manager;
    $this->cache = $cache;
    $this->styleProfileRuleHandlerManager = $style_profile_rule_handler_manager;
    $this->cssCollectionOptimizer = $css_collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public function getJsAssets(AttachedAssetsInterface $assets, $optimize, ?LanguageInterface $language = NULL): array {
    // Delegate to the original service for JS assets.
    return $this->innerAssetResolver->getJsAssets($assets, $optimize, $language);
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

  /**
   * {@inheritdoc}
   */
  public function getLibrariesToLoad(AttachedAssetsInterface $assets): array {
    // Delegate to the original service.
    return $this->innerAssetResolver->getLibrariesToLoad($assets);
  }

}
