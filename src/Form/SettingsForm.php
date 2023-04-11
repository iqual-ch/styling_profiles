<?php

namespace Drupal\styling_profiles\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Defines the Google tag manager module and default container settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styling_profiles_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['styling_profiles.settings'];
  }

}
