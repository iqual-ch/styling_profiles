<?php

namespace Drupal\styling_profiles;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a listing of container configuration entities.
 *
 * @see \Drupal\styling_profiles\Entity\Container
 */
class ProfileListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = t('Label');
    $header['id'] = t('Machine name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
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
        'title' => t('Edit profile'),
        'weight' => 20,
        'url' => $entity->toUrl('edit-form'),
      ];
    }
    return $operations;
  }

}
