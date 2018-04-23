<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for composite element (builder).
 *
 * @group Webform
 */
class WebformElementCompositeTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_composite'];

  /**
   * Test composite (builder).
   */
  public function testComposite() {
    $webform = Webform::load('test_element_composite');

    // Check processing for user who can't edit source.
    $this->postSubmission($webform);
    $this->assertRaw("webform_element_composite_basic:
  first_name:
    '#type': textfield
    '#title': 'First name'
    '#required': true
  last_name:
    '#type': textfield
    '#title': 'Last name'
    '#required': true
webform_element_composite_advanced:
  first_name:
    '#type': textfield
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#title': 'Last name'
  gender:
    '#type': select
    '#options':
      Male: Male
      Female: Female
    '#title': Gender
  martial_status:
    '#type': webform_select_other
    '#options': marital_status
    '#title': 'Martial status'
  employment_status:
    '#type': webform_select_other
    '#options': employment_status
    '#title': 'Employment status'
  age:
    '#type': number
    '#title': Age
    '#field_suffix': ' yrs. old'
    '#min': 1
    '#max': 125");

    // Check processing for user who can edit source.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform);
    $this->assertRaw("webform_element_composite_basic:
  first_name:
    '#type': textfield
    '#title': 'First name'
    '#required': true
  last_name:
    '#type': textfield
    '#title': 'Last name'
    '#required': true
webform_element_composite_advanced:
  first_name:
    '#type': textfield
    '#title': 'First name'
  last_name:
    '#type': textfield
    '#title': 'Last name'
  gender:
    '#type': select
    '#options':
      Male: Male
      Female: Female
    '#title': Gender
  martial_status:
    '#type': webform_select_other
    '#options': marital_status
    '#title': 'Martial status'
  employment_status:
    '#type': webform_select_other
    '#options': employment_status
    '#title': 'Employment status'
  age:
    '#type': number
    '#title': Age
    '#field_suffix': ' yrs. old'
    '#min': 1
    '#max': 125");
  }

}
