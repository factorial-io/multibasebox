<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for likert element.
 *
 * @group Webform
 */
class WebformElementLikertTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_likert'];

  /**
   * Test likert element.
   */
  public function testLikertElement() {

    $this->drupalGet('webform/test_element_likert');

    // Check default likert element.
    $this->assertRaw('<table class="webform-likert-table responsive-enabled" data-likert-answers-count="3" data-drupal-selector="edit-likert-default-table" id="edit-likert-default-table" data-striping="1">');
    $this->assertPattern('#<tr>\s+<th></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+</tr>#');
    $this->assertRaw('<label for="edit-likert-default-table-q1-likert-question">Question 1</label>');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-default-q1 form-item-likert-default-q1">');
    $this->assertRaw('<input data-drupal-selector="edit-likert-default-q1" type="radio" id="edit-likert-default-q1" name="likert_default[q1]" value="1" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-default-q1" class="option"><span class="webform-likert-label">Option 1</span></label>');

    // Check advanced likert element with N/A.
    $this->assertPattern('#<tr>\s+<th></th>\s+<th>Option 1</th>\s+<th>Option 2</th>\s+<th>Option 3</th>\s+<th>Not applicable</th>\s+</tr>#');
    $this->assertRaw('<td><div class="js-form-item form-item js-form-type-radio form-type-radio js-form-item-likert-advanced-q1 form-item-likert-advanced-q1">');
    $this->assertRaw('<input data-drupal-selector="edit-likert-advanced-q1" type="radio" id="edit-likert-advanced-q1--4" name="likert_advanced[q1]" value="N/A" class="form-radio" />');
    $this->assertRaw('<label for="edit-likert-advanced-q1--4" class="option"><span class="webform-likert-label">Not applicable</span></label>');

    // Check likert with description.
    $this->assertRaw('<th>Option 1<div class="description">This is a description</div>');
    $this->assertRaw('<label for="edit-likert-description-table-q1-likert-question">Question 1</label>');
    $this->assertRaw('<div id="edit-likert-description-table-q1-likert-question--description" class="description">');
    $this->assertRaw('<label for="edit-likert-description-q1" class="option"><span class="webform-likert-label">Option 1</span></label>');
    $this->assertRaw('<span class="webform-likert-description">This is a description</span>');

    // Check likert with help.
    $this->assertRaw('<th>Option 1<a href="#help" title="This is a help text" data-webform-help="This is a help text" class="webform-element-help">?</a>');
    $this->assertRaw('<label for="edit-likert-help-table-q1-likert-question">Question 1<a href="#help" title="This is a help text" data-webform-help="This is a help text" class="webform-element-help">?</a>');
    $this->assertRaw('<label for="edit-likert-help-q1--2" class="option"><span class="webform-likert-label">Option 2<a href="#help" title="This is a help text" data-webform-help="This is a help text" class="webform-element-help">?</a>');

    // Check likert required.
    $this->drupalPostForm('webform/test_element_likert', [], t('Submit'));
    $this->assertRaw('Question 1 field is required.');
    $this->assertRaw('Question 2 field is required.');
    $this->assertRaw('Question 3 field is required.');

    // Check likert processing.
    $edit = [
      'likert_advanced[q1]' => '1',
      'likert_advanced[q2]' => '2',
      'likert_advanced[q3]' => 'N/A',
      'likert_values[0]' => '0',
      'likert_values[1]' => '1',
      'likert_values[2]' => 'N/A',
    ];
    $this->drupalPostForm('webform/test_element_likert', $edit, t('Submit'));
    $this->assertRaw("likert_default:
  q1: null
  q2: null
  q3: null
likert_advanced:
  q1: '1'
  q2: '2'
  q3: N/A
likert_description:
  q1: null
  q2: null
  q3: null
likert_help:
  q1: null
  q2: null
  q3: null
likert_values:
  - '0'
  - '1'
  - N/A");
  }

}
