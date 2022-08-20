<?php

namespace Drupal\styling_profiles\Service;

use Drupal\iq_barrio_helper\Service\iqBarrioService;
use Drupal\styling_profiles\Entity\StylingProfile;

/**
 *
 */
class SassManager {

  /**
   * Undocumented variable.
   *
   * @var [type]
   */
  protected $barrioService = NULL;

  /**
   * Undocumented function.
   *
   * @param \Drupal\iq_barrio_helper\Service\iqBarrioService $barrioService
   */
  public function __construct(iqBarrioService $barrioService) {
    $this->barrioService = $barrioService;
  }

  /**
   * Undocumented function.
   *
   * @param \Drupal\styling_profiles\Entity\StylingProfile $profile
   *
   * @return void
   */
  public function provideSass(StylingProfile $profile) {
    $id = $profile->get('id');

    // Clone stylesheets from custom themes.
    $themes = [
      \Drupal::root() . '/themes/custom/iq_barrio',
      \Drupal::root() . '/themes/custom/iq_custom',
    ];

    foreach ($themes as $theme) {
      $themeFiles = array_keys(iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($theme))));
      foreach ($themeFiles as $filename) {
        if (in_array(pathinfo($filename, PATHINFO_EXTENSION), ['scss', 'ini', 'rb'])) {
          $fileDest = str_replace('/themes/custom', '/sites/default/files/styling_profiles/' . $id, $filename);
          $path = pathinfo($fileDest);
          if (!file_exists($path['dirname'])) {
            mkdir($path['dirname'], 0755, TRUE);
          }
          copy($filename, $fileDest);
        }
      }
    }
  }

  /**
   *
   */
  public function writeDefinitionsFile($stylingValues, $pathDefinitionTarget, $pathDefinitionSource = NULL) {
    $this->barrioService->writeDefinitionsFile(
          $stylingValues,
    $pathDefinitionTarget,
    $pathDefinitionSource
    );
  }

}
