<?php

namespace Drupal\webform\Tests\Composite;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for custom composite element.
 *
 * @group Webform
 */
class WebformCompositeCustomTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite_custom'];

  /**
   * Test custom composite element.
   */
  public function testCustom() {

    /* Display */

    $this->drupalGet('webform/test_composite_custom');

    // Check basic custom composite.
    $this->assertRaw('<label for="edit-webform-custom-composite-basic">webform_custom_composite_basic</label>');
    $this->assertRaw('<div id="webform_custom_composite_basic_table" class="webform-multiple-table webform-multiple-table-responsive">');
    $this->assertRaw('<th class="webform_custom_composite_basic-table--handle webform-multiple-table--handle"></th>');
    $this->assertRaw('<th class="webform_custom_composite_basic-table--first_name webform-multiple-table--first_name">First name</th>');
    $this->assertRaw('<th class="webform_custom_composite_basic-table--last_name webform-multiple-table--last_name">Last name</th>');
    $this->assertRaw('<th class="webform_custom_composite_basic-table--weight webform-multiple-table--weight">Weight</th>');

    // Check advanced custom composite.
    $this->assertRaw('<span class="field-suffix"> yrs. old</span>');

    /* Processing */

    // Check contact composite value.
    $this->drupalPostForm('webform/test_composite_custom', [], t('Submit'));
    $this->assertRaw("webform_custom_composite_basic:
  - first_name: John
    last_name: Smith
webform_custom_composite_advanced:
  - first_name: John
    last_name: Smith
    gender: Male
    martial_status: Single
    employment_status: Unemployed
    age: '20'");
  }

}
