<?php

namespace Drupal\styling_profiles\Form;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Builds the form to delete an Example.
 */
class ProfileDeleteForm extends EntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Delete profile in filesystem.
    $profilePath = $_SERVER["DOCUMENT_ROOT"] . '/sites/default/files/styling_profiles/' . $this->entity->id();
    /** @var \Drupal\Core\File\FileSystemInterface $filesystem */
    $filesystem = \Drupal::service('file_system');
    $profilePath = $filesystem->realpath($profilePath);
    if (is_dir($profilePath)) {
      $filesystem->deleteRecursive($profilePath);
    }
    else {
      $filesystem->delete($profilePath);
    }

    $this->entity->delete();
    $this->messenger()->addMessage($this->t('Profile %label has been deleted.', ['%label' => $this->entity->label()]));
    $form_state->setRedirect('entity.styling_profile.collection');
  }

}
