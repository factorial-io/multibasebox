<?php

namespace Drupal\webform\Tests\Element;

/**
 * Tests for webform element #states.
 *
 * @group Webform
 */
class WebformElementStatesTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_states'];

  /**
   * Tests element #states.
   */
  public function testElement() {

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check default value handling.
    $this->drupalPostForm('webform/test_element_states', [], t('Submit'));

    $this->assertRaw("states_basic:
  enabled:
    selector_01:
      checked: true
  required:
    'selector_01''':
      checked: true
    selector_02:
      checked: true
  disabled:
    - selector_01:
        checked: true
    - or
    - selector_02:
        checked: true
states_values:
  enabled:
    selector_01:
      value: '0'
    selector_02:
      value: 'false'
    selector_03:
      value: ''
    selector_04:
      checked: true
states_custom_selector:
  required:
    custom_selector:
      value: 'Yes'
states_empty: {  }
states_single: {  }
states_unsupported_operator:
  required:
    - custom_selector:
        value: 'Yes'
    - xxx
    - custom_selector:
        value: 'Yes'
states_unsupported_nesting:
  required:
    - selector_01:
        value: 'Yes'
      selector_02:
        value: 'Yes'
    - or
    - selector_03:
        value: 'Yes'
      selector_04:
        value: 'Yes'
states_custom_condition:
  required:
    custom_selector:
      value:
        pattern: '[a-z0-9]+'");

    /**************************************************************************/
    // Rendering.
    /**************************************************************************/

    $this->drupalGet('webform/test_element_states');

    // Check 'States custom selector'.
    $this->assertRaw('<option value="custom_selector" selected="selected">custom_selector</option>');

    // Check 'States unsupport operator'.
    $this->assertRaw('Conditional logic (Form API #states) is using the <em class="placeholder">XXX</em> operator. Form API #states must be manually entered.');
    $this->assertRaw('<textarea data-drupal-selector="edit-states-unsupported-operator-states" aria-describedby="edit-states-unsupported-operator-states--description" class="js-webform-codemirror webform-codemirror yaml form-textarea resize-vertical" data-webform-codemirror-mode="text/x-yaml" id="edit-states-unsupported-operator-states" name="states_unsupported_operator[states]" rows="5" cols="60">');

    // Check 'States unsupport nested multiple selectors'.
    $this->assertRaw('Conditional logic (Form API #states) is using multiple nested conditions. Form API #states must be manually entered.');
    $this->assertRaw('<textarea data-drupal-selector="edit-states-unsupported-nesting-states" aria-describedby="edit-states-unsupported-nesting-states--description" class="js-webform-codemirror webform-codemirror yaml form-textarea resize-vertical" data-webform-codemirror-mode="text/x-yaml" id="edit-states-unsupported-nesting-states" name="states_unsupported_nesting[states]" rows="5" cols="60">');

    // Check 'States single' (#multiple: FALSE)
    $this->assertFieldById('edit-states-empty-add');
    $this->assertNoFieldById('edit-states-single-add');

    /**************************************************************************/
    // Processing.
    /**************************************************************************/

    // Check setting first state and adding new state.
    $edit = [
      'states_empty[states][0][state]' => 'required',
      'states_empty[states][1][selector]' => 'selector_01',
      'states_empty[states][1][trigger]' => 'value',
      'states_empty[states][1][value]' => '{value_01}',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'states_empty_table_add');

    // Check the first state/condition is required and value = {value_01}.
    $this->assertFieldByName('states_empty[states][0][state]', 'required');
    $this->assertFieldByName('states_empty[states][1][selector]', 'selector_01');
    $this->assertFieldByName('states_empty[states][1][trigger]', 'value');
    $this->assertFieldByName('states_empty[states][1][value]', '{value_01}');

    // Check empty second state/condition.
    $this->assertFieldByName('states_empty[states][2][state]', '');
    $this->assertFieldByName('states_empty[states][3][selector]', '');
    $this->assertFieldByName('states_empty[states][3][trigger]', '');
    $this->assertFieldByName('states_empty[states][3][value]', '');

    $edit = [
      'states_empty[states][2][state]' => 'disabled',
      'states_empty[states][3][selector]' => 'selector_02',
      'states_empty[states][3][trigger]' => 'value',
      'states_empty[states][3][value]' => '{value_02}',
    ];
    $this->drupalPostAjaxForm(NULL, $edit, 'states_empty_table_remove_1');

    // Check the first condition is removed.
    $this->assertNoFieldByName('states_empty[states][1][selector]', 'selector_01');
    $this->assertNoFieldByName('states_empty[states][1][trigger]', 'value');
    $this->assertNoFieldByName('states_empty[states][1][value]', '{value_01}');

    // Check the second state/condition is required and value = {value_01}.
    $this->assertFieldByName('states_empty[states][1][state]', 'disabled');
    $this->assertFieldByName('states_empty[states][2][selector]', 'selector_02');
    $this->assertFieldByName('states_empty[states][2][trigger]', 'value');
    $this->assertFieldByName('states_empty[states][2][value]', '{value_02}');

    // Remove state two.
    $this->drupalPostAjaxForm(NULL, [], 'states_empty_table_remove_1');

    // Check the second state/condition is removed.
    $this->assertNoFieldByName('states_empty[states][1][state]', 'disabled');
    $this->assertNoFieldByName('states_empty[states][2][selector]', 'selector_02');
    $this->assertNoFieldByName('states_empty[states][2][trigger]', 'value');
    $this->assertNoFieldByName('states_empty[states][2][value]', '{value_02}');
  }

}
