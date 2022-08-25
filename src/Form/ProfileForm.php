<?php

namespace Drupal\styling_profiles\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the styling profile settings form.
 */
class ProfileForm extends EntityForm {

  /**
   * Undocumented variable.
   *
   * @var bool
   */
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

    if ($profile->id()) {
      $form['id']['#disabled'] = TRUE;
    }

    // Load profile styles if profile exists
    // otherwise load barrio settings.
    if ($profile->id()) {
      $styleSettings = $profile->get('styles');
    }
    else {
      $styleSettings = \Drupal::config('iq_barrio.settings')->get();
    }

    $service = \Drupal::service('iq_barrio_helper.iq_barrio_service');
    $service->alterThemeSettingsForm($form, $styleSettings);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    self::$saving = TRUE;
    $profile = $this->entity;

    // Prevent leading and trailing spaces.
    $profile->set('label', trim($form_state->getValue('label')));
    $profile->set('id', $form_state->getValue('id'));

    // Save styles to config.
    $styles = $form_state->getValues();
    unset($styles['id']);
    unset($styles['label']);
    $profile->set('styles', $styles);
    $status = $profile->save();

    // Trigger compilation.
    // @see styling_profiles_iq_scss_compiler_pre_compile
    \Drupal::service('iq_scss_compiler.compilation_service')->compile();

    // Tell the user we've updated the profile.
    $action = $status == SAVED_UPDATED ? 'updated' : 'added';
    \Drupal::messenger()->addStatus($this->t('Profile %label has been %action.', ['%label' => $profile->label(), '%action' => $action]));
    $this->logger('styling_profiles')->notice('Styling profile %label has been %action.', ['%label' => $profile->label(), '%action' => $action]);

    // Redirect back to the list view.
    $form_state->setRedirect('entity.styling_profile.collection');

    if ($form_state->getValue('reset_css')) {
      $service->resetCSS();
    }
    self::$saving = FALSE;
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
