<?php

namespace Drupal\styling_profiles\CacheContext;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 *
 */
class StylingProfiles implements CacheContextInterface {

  /**
   *
   */
  public function __construct() {

  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Style profile cache context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $styleProfileRuleHandlerManager = \Drupal::service('styling_profile.service.rule_handler_manager');
    $handlers = $styleProfileRuleHandlerManager->getHandlers();
    $profile = '';
    foreach($handlers as $handler) {
      $profile = $handler->getProfile($profile);
    }
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
