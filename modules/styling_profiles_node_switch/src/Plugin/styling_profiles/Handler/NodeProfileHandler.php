<?php

namespace Drupal\styling_profiles_node_switch\Plugin\styling_profiles\Handler;

use Drupal\styling_profiles\Plugin\styling_profiles\Handler\DefaultHandler;
use Drupal\node\NodeInterface;

/**
 * Process profile selection with node switch.
 *
 * @StylingProfilesHandler(
 *   id = "node_profile",
 *   name = @Translation("Node profile selector"),
 *   weight = 200
 * )
 */
class NodeProfileHandler extends DefaultHandler {

  /**
   * Get Profile.
   */
  public function getProfile(string $profile) {
    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface && $node->field_styling_profile && $node->field_styling_profile->target_id) {
      return $node->field_styling_profile->target_id;
    }
    else {
      return $profile;
    }
  }

}
