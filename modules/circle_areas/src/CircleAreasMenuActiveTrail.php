<?php
/**
 * @file
 * Contains Drupal\custom\CustomMenuActiveTrail.
 */

namespace Drupal\circle_areas;

use Drupal\Core\Menu\MenuActiveTrail;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;

/**
 * Extend the MenuActiveTrail class.
 */
class CircleAreasMenuActiveTrail extends MenuActiveTrail {

  /**
   * {@inheritdoc}
   */
  public function getActiveLink($menu_name = NULL) {
    // Call the parent method to implement the default behavior.
    $found = parent::getActiveLink($menu_name);

    $node = $this->routeMatch->getParameter('node');
    if ($menu_name == 'main-menu' && $node && in_array(
      $node->type->entity->id(), 
      array('rental_floor', 'rental_house', 'rental_overview')
      )
    ) {
      $links = $this->menuLinkManager->loadLinksByRoute('entity.node.canonical', array('node' => "23"), $menu_name);
      $route_parameters = $this->routeMatch->getRawParameters()->all();

      if ($links) {
        $found = reset($links);
      }
    }

    return $found;
  }
}