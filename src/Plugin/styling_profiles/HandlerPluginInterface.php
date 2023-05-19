<?php

namespace Drupal\styling_profiles\Plugin\styling_profiles;

/**
 * Handler Plugin Interface.
 */
interface HandlerPluginInterface {

  /**
   * Select a profile to be used.
   *
   * @param string $profile
   *   The currently selected profile.
   *
   * @return string
   *   The profile selected by the handler.
   */
  public function getProfile(string $profile);

}
