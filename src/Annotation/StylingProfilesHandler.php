<?php

namespace Drupal\styling_profiles\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a reusable form plugin annotation object.
 *
 * @Annotation
 */
class StylingProfilesHandler extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The name of the form plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $name;

  /**
   * The weight of the handler (defaults to 100)
   *
   * @var int
   */
  public $weight = 100;

}
