<?php

namespace Drupal\webform\Tests\Composite;

use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for composite elements.
 *
 * @group Webform
 */
class WebformCompositeTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_composite'];

  /**
   * Test composite element.
   */
  public function testComposite() {

    /* Display */

    $this->drupalGet('webform/test_composite');

    // Check webform contact basic.
    $this->assertRaw('<div id="edit-contact-basic--wrapper" class="form-composite js-form-item form-item js-form-type-webform-contact form-type-webform-contact js-form-item-contact-basic form-item-contact-basic form-no-label">');
    $this->assertRaw('<label for="edit-contact-basic-name" class="js-form-required form-required">Name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-basic-name" type="text" id="edit-contact-basic-name" name="contact_basic[name]" value="John Smith" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check custom name title, description, and required.
    $this->assertRaw('<label for="edit-contact-advanced-name" class="js-form-required form-required">Custom contact name</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-name" aria-describedby="edit-contact-advanced-name--description" type="text" id="edit-contact-advanced-name" name="contact_advanced[name]" value="John Smith" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');
    $this->assertRaw('Custom contact name description');

    // Check custom state type and not required.
    $this->assertRaw('<label for="edit-contact-advanced-state-province">State/Province</label>');
    $this->assertRaw('<input data-drupal-selector="edit-contact-advanced-state-province" type="text" id="edit-contact-advanced-state-province" name="contact_advanced[state_province]" value="New Jersey" size="60" maxlength="255" class="form-text" />');

    // Check custom country access.
    $this->assertNoRaw('edit-contact-advanced-country');

    // Check link multiple in table.
    $this->assertRaw('<label for="edit-link-multiple">Link multiple</label>');
    $this->assertRaw('<th class="link_multiple-table--title webform-multiple-table--title">Link Title<a href="#help" title="This is link title help" data-webform-help="This is link title help" class="webform-element-help">?</a>');
    $this->assertRaw('<th class="link_multiple-table--url webform-multiple-table--url">Link URL<a href="#help" title="This is link url help" data-webform-help="This is link url help" class="webform-element-help">?</a>');

    /* Processing */

    // Check contact composite value.
    $this->drupalPostForm('webform/test_composite', [], t('Submit'));
    $this->assertRaw("contact_basic:
  name: 'John Smith'
  company: Acme
  email: example@example.com
  phone: 123-456-7890
  address: '100 Main Street'
  address_2: 'PO BOX 999'
  city: 'Hill Valley'
  state_province: 'New Jersey'
  postal_code: 11111-1111
  country: 'United States'");

    // Check contact validate required composite elements.
    $edit = [
      'contact_basic[name]' => '',
    ];
    $this->drupalPostForm('webform/test_composite', $edit, t('Submit'));
    $this->assertRaw('Name field is required.');
  }

}
