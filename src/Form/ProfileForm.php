<?php

namespace Drupal\styling_profiles\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the Google tag manager profile settings form.
 */
class ProfileForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $profile = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => 'Label',
      '#default_value' => $profile->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $profile->id(),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'profileExists'],
        'replace_pattern' => '[^a-z0-9_.]+',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $profile = $this->entity;

    // Prevent leading and trailing spaces.
    $profile->set('label', trim($form_state->getValue('label')));
    $profile->set('id', $form_state->getValue('id'));
    $status = $profile->save();

    $edit_link = $this->entity->link($this->t('Edit'));
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';

    // Tell the user we've updated their ball.
    drupal_set_message($this->t('Profile %label has been %action.', ['%label' => $profile->label(), '%action' => $action]));
    $this->logger('sample_config_entity')->notice('Styling profile %label has been %action.', ['%label' => $profile->label(), 'link' => $edit_link]);

    // Redirect back to the list view.
    $form_state->setRedirect('entity.styling_profile.collection');

  }

  /**
   * Checks if a profile machine name is taken.
   *
   * @param string $value
   *   The machine name.
   * @param array $element
   *   An array containing the structure of the 'id' element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return bool
   *   Whether or not the profile machine name is taken.
   */
  public function profileExists($value, array $element, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $profile */
    $profile = $form_state->getFormObject()->getEntity();
    return (bool) $this->entityTypeManager->getStorage($profile->getEntityTypeId())
      ->getQuery()
      ->condition($profile->getEntityType()->getKey('id'), $value)
      ->execute();
  }

}
