<?php

namespace Drupal\styling_profiles\CacheContext;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\styling_profiles\Service\RuleHandlerManager;

/**
 * Styling Profiles class.
 */
class StylingProfiles implements CacheContextInterface {

  /**
   * Rule Handler Manager.
   *
   * @var Drupal\styling_profiles\Service\RuleHandlerManager
   */
  protected $ruleHandlerManager;

  /**
   * The class constructor.
   *
   * @param Drupal\styling_profiles\Service\RuleHandlerManager $rule_handler_manager
   *   The rule handler manager.
   */
  public function __construct(RuleHandlerManager $rule_handler_manager) {
    $this->ruleHandlerManager = $rule_handler_manager;
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
    return $this->ruleHandlerManager->getStylingProfile();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
