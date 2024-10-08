<?php

namespace Drupal\styling_profiles\Asset;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Asset\AssetResolver as CoreAssetResolver;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Custom Asset Resolver class dependent on the styling profile.
 */
class AssetResolver extends CoreAssetResolver {


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
    $styleProfileRuleHandlerManager = \Drupal::service('styling_profile.service.rule_handler_manager');
    $stylingProfile = $styleProfileRuleHandlerManager->getStylingProfile();
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

    foreach ($libraries_to_load as $key => $library) {
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
        $css = \Drupal::service('asset.css.collection_optimizer')->optimize($css, array_values($libraries_to_load), $language);
      }
    }
    $this->cache->set($cid, $css, CacheBackendInterface::CACHE_PERMANENT, ['library_info']);

    return $css;
  }

}
