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
   * Creates a new SassManager.
   *
   * @param \Drupal\iq_barrio_helper\Service\iqBarrioService $barrioService
   *   The barrio helper service.
   */
  public function __construct(iqBarrioService $barrioService) {
    $this->barrioService = $barrioService;
  }

  /**
   * Copy the themes sass files for the given profile.
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
   * Write the definitions file for the profile.
   *
   * @param StylingProfile $profile
   *   The profile to write the file for.
   */
  public function writeDefinitionsFile(StylingProfile $profile) {
    $stylingValues = $profile->get('styles');
    $pathDefinitionTarget = \Drupal::root() . '/sites/default/files/styling_profiles/' . $profile->id() . '/iq_barrio/resources/sass/_definitions.scss';
    $pathDefinitionSource = \Drupal::root() . '/themes/custom/iq_barrio/resources/sass/_template.scss.txt';

    $this->barrioService->writeDefinitionsFile(
      $stylingValues,
      $pathDefinitionTarget,
      $pathDefinitionSource
    );
  }
}
