<?php

namespace Drupal\styling_profiles;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Defines a listing of container configuration entities.
 *
 * @see \Drupal\styling_profiles\Entity\Container
 */
class ProfileListBuilder extends ConfigEntityListBuilder {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = [];
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = [
        'title' => $this->t('Edit profile'),
        'weight' => 20,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    return $operations;
  }

}
