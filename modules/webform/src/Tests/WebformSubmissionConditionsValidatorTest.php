<?php

namespace Drupal\webform\Tests;

use Drupal\webform\Element\WebformOtherBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission conditions (#states) validator.
 *
 * @group Webform
 */
class WebformSubmissionConditionsValidatorTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_form_states_server_custom',
    'test_form_states_server_comp',
    'test_form_states_server_multiple',
    'test_form_states_server_nested',
    'test_form_states_server_preview',
    'test_form_states_server_required',
    'test_form_states_server_wizard',
  ];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();

    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests webform submission conditions (#states) validator required.
   */
  public function testFormStatesValidatorRequired() {
    $webform = Webform::load('test_form_states_server_required');

    // Check no #states required errors.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Form API #states server-side required validation.');

    /**************************************************************************/
    // multiple_triggers.
    /**************************************************************************/

    // Check required multiple dependents 'AND' and 'OR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
      'trigger_textfield' => '{value}',
      'trigger_select' => 'option',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_and field is required.');
    $this->assertRaw('dependent_textfield_required_or field is required.');
    $this->assertNoRaw('dependent_textfield_required_xor field is required.');

    /**************************************************************************/
    // multiple_dependents.
    /**************************************************************************/

    // Check required multiple dependents 'OR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('dependent_textfield_required_and field is required.');
    $this->assertRaw('dependent_textfield_required_or field is required.');

    // Check required multiple dependents 'XOR' operator.
    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_xor field is required.');

    $edit = [
      'trigger_checkbox' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('dependent_textfield_required_xor field is required.');

    /**************************************************************************/
    // required_hidden_trigger.
    /**************************************************************************/

    $edit = [
      'required_hidden_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('required_hidden_dependent_required field is required.');

    /**************************************************************************/
    // minlength_hidden_trigger
    /**************************************************************************/

    $edit = [
      'minlength_hidden_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('<em class="placeholder">minlength_hidden_dependent</em> cannot be less than <em class="placeholder">1</em> characters but is currently <em class="placeholder">0</em> characters long.');

    /**************************************************************************/
    // checkboxes_trigger.
    /**************************************************************************/

    // Check required checkboxes.
    $edit = [
      'checkboxes_trigger[one]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('checkboxes_dependent_required field is required.');

    /**************************************************************************/
    // text_format_trigger.
    /**************************************************************************/

    // Check required text_format.
    $edit = [
      'text_format_trigger[format]' => 'full_html',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('text_format_dependent_required field is required.');

    /**************************************************************************/
    // select_other_trigger.
    /**************************************************************************/

    // Check required webform_select_other select #options.
    $edit = [
      'select_other_trigger[select]' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_dependent_required field is required.');

    // Check required webform_select_other other textfield.
    $edit = [
      'select_other_trigger[select]' => WebformOtherBase::OTHER_OPTION,
      'select_other_trigger[other]' => '{value}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_dependent_required field is required.');

    /**************************************************************************/
    // select_other_multiple_trigger.
    /**************************************************************************/

    // Check required webform_select_other_multiple select #options.
    $edit = [
      'select_other_multiple_trigger[select][]' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_other_multiple_dependent_required field is required.');

    /**************************************************************************/
    // select_values_trigger.
    /**************************************************************************/

    // Check required select_values_trigger select option 'one'.
    $edit = [
      'select_values_trigger' => 'one',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_values_trigger_dependent_required field is required.');

    // Check required select_values_trigger select option 'two'.
    $edit = [
      'select_values_trigger' => 'two',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('select_values_trigger_dependent_required field is required.');

    /**************************************************************************/
    // email_confirm_trigger.
    /**************************************************************************/

    // Check required webform_email_confirm.
    $edit = [
      'email_confirm_trigger[mail_1]' => 'example@example.com',
      'email_confirm_trigger[mail_2]' => 'example@example.com',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('email_confirm_dependent_required field is required.');

    /**************************************************************************/
    // likert_trigger.
    /**************************************************************************/

    // Check required webform_likert.
    $edit = [
      'likert_trigger[q1]' => 'a1',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('likert_dependent_required field is required.');

    /**************************************************************************/
    // datelist_trigger.
    /**************************************************************************/

    // Check required datelist.
    $edit = [
      'datelist_trigger[year]' => date('Y'),
      'datelist_trigger[month]' => 1,
      'datelist_trigger[day]' => 1,
      'datelist_trigger[hour]' => 1,
      'datelist_trigger[minute]' => 1,
      'datelist_trigger[second]' => 1,
      'datelist_trigger[ampm]' => 'am',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('datelist_dependent_required field is required.');

    /**************************************************************************/
    // datetime_trigger.
    /**************************************************************************/

    // Check required datetime.
    $edit = [
      'datetime_trigger[date]' => date('2001-01-01'),
      'datetime_trigger[time]' => date('12:12:12'),
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('datetime_dependent_required field is required.');

    /**************************************************************************/
    // address_trigger.
    /**************************************************************************/

    // Check required address.
    $edit = [
      'address_trigger[address]' => '{value}',
      'address_trigger[address_2]' => '{value}',
      'address_trigger[city]' => '{value}',
      'address_trigger[state_province]' => 'Alabama',
      'address_trigger[postal_code]' => '11111',
      'address_trigger[country]' => 'Afghanistan',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('address_dependent_required field is required.');

    /**************************************************************************/
    // composite_required.
    /**************************************************************************/

    // Check required composite.
    $edit = [
      'composite_required_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('composite_required_dependent field is required.');

    // Check required composite subelements.
    $edit = [
      'composite_sub_elements_required_trigger' => 'a',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('address_a field is required.');
    $this->assertRaw('city_a field is required.');
    $this->assertRaw('state_province_a field is required.');
    $this->assertRaw('postal_code_a field is required.');
    $this->assertRaw('country_a field is required.');
    $this->assertNoRaw('address_b field is required.');
    $this->assertNoRaw('city_b field is required.');
    $this->assertNoRaw('state_province_b field is required.');
    $this->assertNoRaw('postal_code_b field is required.');
    $this->assertNoRaw('country_b field is required.');

    $edit = [
      'composite_sub_elements_required_trigger' => 'b',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('address_a field is required.');
    $this->assertNoRaw('city_a field is required.');
    $this->assertNoRaw('state_province_a field is required.');
    $this->assertNoRaw('postal_code_a field is required.');
    $this->assertNoRaw('country_a field is required.');
    $this->assertRaw('address_b field is required.');
    $this->assertRaw('city_b field is required.');
    $this->assertRaw('state_province_b field is required.');
    $this->assertRaw('postal_code_b field is required.');
    $this->assertRaw('country_b field is required.');

    /**************************************************************************/
    // custom.
    /**************************************************************************/

    $webform = Webform::load('test_form_states_server_custom');

    // Check no #states required errors.
    $this->postSubmission($webform);
    $this->assertRaw('New submission added to Test: Form API #states custom pattern, less, and greater condition validation');

    $edit = [
      'trigger_pattern' => 'abc',
      'trigger_not_pattern' => 'ABC',
      'trigger_less' => 1,
      'trigger_greater' => 11,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('New submission added to Test: Form API #states custom pattern, less, and greater condition validation');
    $this->assertRaw('dependent_pattern field is required.');
    $this->assertRaw('dependent_not_pattern field is required.');
    $this->assertRaw('dependent_less field is required.');
    $this->assertRaw('dependent_greater field is required.');

    /**************************************************************************/
    // multiple element.
    /**************************************************************************/

    $webform = Webform::load('test_form_states_server_multiple');

    $edit = [
      'trigger_required' => TRUE,
    ];
    $this->postSubmission($webform, $edit);

    // Check multiple error.
    $this->assertRaw('textfield_multiple field is required.');

    /**************************************************************************/
    // composite element.
    /**************************************************************************/

    $webform = Webform::load('test_form_states_server_comp');

    $edit = [
      'webform_name_trigger' => TRUE,
      'webform_name_multiple_trigger' => TRUE,
      'webform_name_multiple_header_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);

    // Check basic composite.
    $this->assertRaw('First field is required.');
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-first" type="text" id="edit-webform-name-first" name="webform_name[first]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022webform_name_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check multiple composite with custom error.
    $this->assertRaw("Custom error message for &#039;last&#039; element.");
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-multiple-items-0-item-last" type="text" id="edit-webform-name-multiple-items-0-item-last" name="webform_name_multiple[items][0][_item_][last]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022webform_name_multiple_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    // Check multiple table composite.
    $this->assertRaw('Last field is required.');
    $this->assertRaw('<input data-drupal-selector="edit-webform-name-multiple-header-items-0-last" type="text" id="edit-webform-name-multiple-header-items-0-last" name="webform_name_multiple_header[items][0][last]" value="" size="60" maxlength="255" class="form-text error" aria-invalid="true" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022webform_name_multiple_header_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');

    /**************************************************************************/
    // nested.
    /**************************************************************************/

    $webform = Webform::load('test_form_states_server_nested');

    // Check sub elements.
    $this->drupalGet('webform/test_form_states_server_nested');
    $this->assertRaw('<input data-drupal-selector="edit-visible-textfield" type="text" id="edit-visible-textfield" name="visible_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-custom-textfield" type="text" id="edit-visible-custom-textfield" name="visible_custom_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true},&quot;:input[name=\u0022visible_textfield\u0022]&quot;:{&quot;filled&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-slide-textfield" type="text" id="edit-visible-slide-textfield" name="visible_slide_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true}}}" />');
    $this->assertRaw('<input data-drupal-selector="edit-visible-slide-custom-textfield" type="text" id="edit-visible-slide-custom-textfield" name="visible_slide_custom_textfield" value="" size="60" maxlength="255" class="form-text" data-drupal-states="{&quot;required&quot;:{&quot;:input[name=\u0022visible_trigger\u0022]&quot;:{&quot;checked&quot;:true},&quot;:input[name=\u0022visible_slide_textfield\u0022]&quot;:{&quot;filled&quot;:true}}}" />');

    // Check nested element is required.
    $edit = [
      'visible_trigger' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('visible_textfield field is required.');
    $this->assertNoRaw('visible_custom_textfield field is required.');
    $this->assertRaw('visible_slide_textfield field is required.');
    $this->assertNoRaw('visible_slide_custom_textfield field is required.');

    // Check nested element is not required.
    $edit = [];
    $this->postSubmission($webform, $edit);
    $this->assertNoRaw('visible_textfield field is required.');
    $this->assertNoRaw('visible_custom_textfield field is required.');
    $this->assertNoRaw('visible_slide_textfield field is required.');
    $this->assertNoRaw('visible_slide_custom_textfield field is required.');

    // Check custom states element validation.
    $edit = [
      'visible_trigger' => TRUE,
      'visible_textfield' => '{value}',
      'visible_slide_textfield' => '{value}',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('visible_custom_textfield field is required.');
    $this->assertRaw('visible_slide_custom_textfield field is required.');
  }

  /**
   * Tests webform submission conditions (#states) validator wizard cross-page conditions.
   */
  public function testFormStatesValidatorWizard() {
    $webform = Webform::load('test_form_states_server_wizard');

    /**************************************************************************/

    // Go to default #states for page 02 with trigger-checkbox unchecked.
    $this->postSubmission($webform, [], t('Next Page >'));

    // Check trigger-checkbox value is No.
    $this->assertRaw('<input data-drupal-selector="edit-page-01-trigger-checkbox-computed" type="hidden" name="page_01_trigger_checkbox_computed" value="No" />');

    // Check page_02_textfield_required is not required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-required" aria-describedby="edit-page-02-textfield-required--description" type="text" id="edit-page-02-textfield-required" name="page_02_textfield_required" value="{default_value}" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_optional is required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-optional" aria-describedby="edit-page-02-textfield-optional--description" type="text" id="edit-page-02-textfield-optional" name="page_02_textfield_optional" value="{default_value}" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check page_02_textfield_disabled is not disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-disabled" aria-describedby="edit-page-02-textfield-disabled--description" type="text" id="edit-page-02-textfield-disabled" name="page_02_textfield_disabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_enabled is disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-enabled" aria-describedby="edit-page-02-textfield-enabled--description" disabled="disabled" type="text" id="edit-page-02-textfield-enabled" name="page_02_textfield_enabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_visible is not visible.
    $this->assertNoFieldByName('page_02_textfield_visible');

    // Check page_02_textfield_visible_slide is not visible.
    $this->assertNoFieldByName('page_02_textfield_visible_slide');

    // Check page_02_textfield_invisible is visible.
    $this->assertFieldByName('page_02_textfield_invisible');

    // Check page_02_textfield_invisible_slide is visible.
    $this->assertFieldByName('page_02_textfield_invisible_slide');

    // Check page_02_checkbox_checked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_details_expanded is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper"> ');

    // Check page_02_details_collapsed is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper" open="open">');

    /**************************************************************************/

    // Go to default #states for page 02 with trigger_checkbox checked.
    $this->postSubmission($webform, ['page_01_trigger_checkbox' => TRUE], t('Next Page >'));

    // Check trigger-checkbox value is Yes.
    $this->assertRaw('<input data-drupal-selector="edit-page-01-trigger-checkbox-computed" type="hidden" name="page_01_trigger_checkbox_computed" value="Yes" />');

    // Check page_02_textfield_required is required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-required" aria-describedby="edit-page-02-textfield-required--description" type="text" id="edit-page-02-textfield-required" name="page_02_textfield_required" value="{default_value}" size="60" maxlength="255" class="form-text required" required="required" aria-required="true" />');

    // Check page_02_textfield_optional is not required.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-optional" aria-describedby="edit-page-02-textfield-optional--description" type="text" id="edit-page-02-textfield-optional" name="page_02_textfield_optional" value="{default_value}" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_disabled is disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-disabled" aria-describedby="edit-page-02-textfield-disabled--description" disabled="disabled" type="text" id="edit-page-02-textfield-disabled" name="page_02_textfield_disabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_enabled is not disabled.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-textfield-enabled" aria-describedby="edit-page-02-textfield-enabled--description" type="text" id="edit-page-02-textfield-enabled" name="page_02_textfield_enabled" value="" size="60" maxlength="255" class="form-text" />');

    // Check page_02_textfield_visible is visible.
    $this->assertFieldByName('page_02_textfield_visible');

    // Check page_02_textfield_visible_slide is visible.
    $this->assertFieldByName('page_02_textfield_visible_slide');

    // Check page_02_textfield_invisible is not visible.
    $this->assertNoFieldByName('page_02_textfield_invisible');

    // Check page_02_textfield_invisible_slide is not visible.
    $this->assertNoFieldByName('page_02_textfield_invisible_slide');

    // Check page_02_checkbox_checked is checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-checked" aria-describedby="edit-page-02-checkbox-checked--description" type="checkbox" id="edit-page-02-checkbox-checked" name="page_02_checkbox_checked" value="1" checked="checked" class="form-checkbox" />');

    // Check page_02_checkbox_unchecked is not checked.
    $this->assertRaw('<input data-drupal-selector="edit-page-02-checkbox-unchecked" aria-describedby="edit-page-02-checkbox-unchecked--description" type="checkbox" id="edit-page-02-checkbox-unchecked" name="page_02_checkbox_unchecked" value="1" class="form-checkbox" />');

    // Check page_02_details_expanded is open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_expanded" data-drupal-selector="edit-page-02-details-expanded" aria-describedby="edit-page-02-details-expanded--description" id="edit-page-02-details-expanded" class="js-form-wrapper form-wrapper" open="open">');

    // Check page_02_details_collapsed is not open.
    $this->assertRaw('<details data-webform-details-nosave data-webform-key="page_02_details_collapsed" data-drupal-selector="edit-page-02-details-collapsed" aria-describedby="edit-page-02-details-collapsed--description" id="edit-page-02-details-collapsed" class="js-form-wrapper form-wrapper">');
  }

  /**
   * Tests conditions (#states) validator for elements .
   */
  public function testStatesValidatorElementVisible() {
    $webform = Webform::load('test_form_states_server_preview');

    // Check trigger unchecked and elements are conditionally hidden.
    $this->postSubmission($webform, [], t('Preview'));
    $this->assertRaw('trigger_checkbox');
    $this->assertNoRaw('dependent_checkbox');
    $this->assertNoRaw('dependent_markup');
    $this->assertNoRaw('dependent_message');
    $this->assertNoRaw('dependent_fieldset');
    $this->assertNoRaw('nested_textfield');

    // Check trigger checked and elements are conditionally visible.
    $this->postSubmission($webform, ['trigger_checkbox' => TRUE], t('Preview'));
    $this->assertRaw('trigger_checkbox');
    $this->assertRaw('dependent_checkbox');
    $this->assertRaw('dependent_markup');
    $this->assertRaw('dependent_message');
    $this->assertRaw('dependent_fieldset');
    $this->assertRaw('nested_textfield');
  }

}

