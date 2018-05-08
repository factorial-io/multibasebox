<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Webform source entity plugin manager.
 */
class WebformSourceEntityManager extends DefaultPluginManager implements WebformSourceEntityManagerInterface {

  /**
   * Constructs a WebformSourceEntityManager object.
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
      'Plugin/WebformSourceEntity',
      $namespaces,
      $module_handler,
      'Drupal\webform\Plugin\WebformSourceEntityInterface',
      'Drupal\webform\Annotation\WebformSourceEntity'
    );
    $this->alterInfo('webform_source_entity_info');
    $this->setCacheBackend($cache_backend, 'webform_source_entity_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceEntity($ignored_types = []) {
    if (!is_array($ignored_types)) {
      $ignored_types = [$ignored_types];
    }

    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      /** @var WebformSourceEntityInterface $instance */
      $instance = $this->createInstance($plugin_id);
      $source_entity = $instance->getSourceEntity($ignored_types);
      if ($source_entity) {
        return $source_entity;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterDefinitions(&$definitions) {
    parent::alterDefinitions($definitions);

    // Additionally sort by weight so we always have them sorted in proper
    // order.
    uasort($definitions, [SortArray::class, 'sortByWeightElement']);
  }

}
