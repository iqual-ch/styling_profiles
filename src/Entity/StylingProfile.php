<?php

namespace Drupal\styling_profiles\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the container configuration entity.
 *
 * @ConfigEntityType(
 *   id = "styling_profile",
 *   label = @Translation("Styling profile configuration"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "form" = {
 *       "default" = "Drupal\styling_profiles\Form\ProfileForm",
 *       "delete" = "Drupal\styling_profiles\Form\ProfileDeleteForm"
 *     },
 *     "list_builder" = "Drupal\styling_profiles\ProfileListBuilder"
 *   },
 *   admin_permission = "administer styling profiles",
 *   config_prefix = "styling_profile",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "styles" = "styles",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/styling-profiles/add",
 *     "edit-form" = "/admin/config/system/styling-profiles/{styling_profile}/edit",
 *     "delete-form" = "/admin/config/system/styling-profiles/{styling_profile}/delete",
 *   }
 * )
 *
 * @todo Add a clone operation.
 * this may not be an option in above annotation
 *     "clone-form" = "/admin/structure/google_tag/manage/{styling_profile}/clone",
 */
class StylingProfile extends ConfigEntityBase {

  /**
   * Profile machine name.
   *
   * @var string
   */
  public $id;

  /**
   * Profile human readable name.
   *
   * @var string
   */
  public $label;

}
