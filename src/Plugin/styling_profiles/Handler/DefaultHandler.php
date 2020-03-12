<?php

namespace Drupal\styling_profiles\Plugin\styling_profiles\Handler;

use Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface;

/**
 * Process profile selection with domain switch.
 *
 * @StylingProfilesHandler(
 *   id = "default_handler",
 *   name = @Translation("Default profile selector"),
 * )
 */
class DefaultHandler implements HandlerPluginInterface {

  /**
   * Select a profile to be used.
   *
   * @param string $profile
   *   The currently selected profile.
   *
   * @return string
   *   The profile selected by the handler.
   */
  public function getProfile(string $profile) {
    return $profile;
  }

}
