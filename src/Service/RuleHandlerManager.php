<?php

namespace Drupal\styling_profiles\Service;

use Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface;
use Drupal\styling_profiles\Annotation\StylingProfilesHandler;
use Drupal\Component\Utility\SortArray;
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
          HandlerPluginInterface::class,
          StylingProfilesHandler::class
      );
    $this->alterInfo('styling_profiles_handler_info');
    $this->setCacheBackend($cache_backend, 'styling_profiles_handler_info_plugins');
  }

  /**
   * Returns all handlers, sorted by weight.
   *
   * @return \Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface[]
   *   A list of Handler objects.
   */
  public function getHandlers() {
    $handlers = [];
    $definitions = $this->getDefinitions();
    uasort($definitions, ['\\' . SortArray::class, 'sortByWeightElement']);
    foreach ($definitions as $definition) {
      $handlers[] = $this->getFactory()->createInstance($definition['id']);
    }
    return $handlers;
  }

  /**
   * Get current styling profile.
   *
   * @return \Drupal\styling_profiles\Plugin\styling_profiles\HandlerPluginInterface[]
   *   A list of Handler objects.
   */
  public function getStylingProfile() {
    $handlers = $this->getHandlers();
    $profile = '';
    foreach ($handlers as $handler) {
      $profile = $handler->getProfile($profile);
    }
    return $profile;
  }

  /**
   * {@inheritdoc}
   */
  public function getFallbackPluginId($plugin_id, array $configuration = []) {
    return 'default_handler';
  }

}
