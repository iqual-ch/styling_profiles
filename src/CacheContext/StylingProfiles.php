<?php

namespace Drupal\styling_profiles\CacheContext;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;

/**
 * Styling Profiles class.
 */
class StylingProfiles implements CacheContextInterface {

  /**
   * The class constructor.
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
    return $styleProfileRuleHandlerManager->getStylingProfile();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
