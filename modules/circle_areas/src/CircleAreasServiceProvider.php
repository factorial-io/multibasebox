<?php
namespace Drupal\circle_areas;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter the service container to use a custom class.
 */
class CircleAreasServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.active_trail');

    $definition->setClass('Drupal\circle_areas\CircleAreasMenuActiveTrail');
  }
}