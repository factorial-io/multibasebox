<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Defines an abstract test base for webform element tests.
 */
abstract class WebformElementTestBase extends WebformTestBase {

  /**
   * Assert element preview.
   *
   * @param string $label
   *   The element's label.
   * @param string $value
   *   The element's value.
   */
  protected function assertElementPreview($label, $value) {
    $this->assertPattern('/<label>' . preg_quote($label, '/') . '<\/label>\s+' . preg_quote($value, '/') . '/');
  }

}
