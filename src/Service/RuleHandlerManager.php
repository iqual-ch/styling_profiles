<?php
namespace Drupal\styling_profiles\Service;

use Drupal\Component\Plugin\FallbackPluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Handler manager for style profile handlers.
 *
 * @see \Drupal\Core\Archiver\Annotation\Archiver
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 */
class RuleHandlerManager extends DefaultPluginManager implements FallbackPluginManagerInterface {

  /**
   * Constructs a RuleHandlerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
          'Plugin/styling_profiles/Handler',
          $namespaces,
          $module_handler,
          'Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface',
          'Drupal\styling_profiles\Annotation\StylingProfilesHandler'
      );
    $this->alterInfo('styling_profiles_handler_info');
    $this->setCacheBackend($cache_backend, 'styling_profiles_handler_info_plugins');
  }

  /**
   * Overrides PluginManagerBase::getInstance().
   *
   * @param array $options
   *   An array with the following key/value pairs:
   *   - id: The id of the plugin.
   *   - type: The type of the pattern field.
   *
   * @return \Drupal\pagedesigner\Plugin\pagedesigner\HandlerPluginInterface[]
   *   A list of Handler objects.
   */
  // public function getInstance(array $options) {
  //   $handlers = [];
  //   $definitions = [];
  //   $type = '*';
  //   $configuration = [];
  //   if (!empty($options['entity'])) {
  //     $type = $options['entity']->bundle();
  //   }
  //   else {
  //     $type = $options['type'];
  //   }
  //   $allDefinitions = $this->getDefinitions();
  //   foreach ($allDefinitions as $plugin_id => $definition) {
  //     if (in_array($type, $definition['types'])) {
  //       $definitions[$plugin_id] = $definition;
  //     }
  //   }
  //   if (count($definitions)) {
  //
  //     foreach ($definitions as $plugin_id => $definition) {
  //       $handlers[] = $this
  //         ->createInstance($plugin_id, $configuration);
  //     }
  //   }
  //   if (empty($handlers)) {
  //     $handlers[] = $this
  //       ->createInstance($this->getFallbackPluginId($type), $configuration);
  //   }
  //   return $handlers;
  // }

  /**
   * Returns all handlers, sorted by weight.
   *
   * @return \Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface[]
   *   A list of Handler objects.
   */
  public function getHandlers() {
    $handlers = [];
    $definitions = $this->getDefinitions();
    uasort($definitions, ['\Drupal\Component\Utility\SortArray', 'sortByWeightElement']);
    foreach ($definitions as $definition) {
      $handlers[] = $this->getFactory()->createInstance($definition['id']);
    }
    return $handlers;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'default_handler';
  }

}
