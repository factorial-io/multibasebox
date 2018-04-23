<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Interface of a webform source entity plugin.
 */
interface WebformSourceEntityInterface extends PluginInspectionInterface {

  /**
   * Detect and return a source entity from current context.
   *
   * @param string[] $ignored_types
   *   Entity types that may not be source entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Source entity or NULL should no source entity be found.
   */
  public function getSourceEntity(array $ignored_types);

}
