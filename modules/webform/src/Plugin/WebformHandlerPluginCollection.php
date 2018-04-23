<?php

namespace Drupal\webform\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\webform\Plugin\WebformHandler\BrokenWebformHandler;

/**
 * A collection of webform handlers.
 */
class WebformHandlerPluginCollection extends DefaultLazyPluginCollection {

  /**
   * {@inheritdoc}
   */
  public function sortHelper($a_id, $b_id) {
    $a_weight = $this->get($a_id)->getWeight();
    $b_weight = $this->get($b_id)->getWeight();
    if ($a_weight == $b_weight) {
      return 0;
    }

    return ($a_weight < $b_weight) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($instance_id) {
    parent::initializePlugin($instance_id);

    // If the initialized handler is broken preserve the original
    // handler's plugin ID.
    // @see \Drupal\webform\Plugin\WebformHandler\BrokenWebformHandler::setPluginId
    $plugin = $this->get($instance_id);
    if ($plugin instanceof BrokenWebformHandler) {
      $original_id = $this->configurations[$instance_id]['id'];
      $plugin->setPluginId($original_id);
    }
  }

}
