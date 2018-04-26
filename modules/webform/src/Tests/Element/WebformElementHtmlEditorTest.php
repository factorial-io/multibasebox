<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform HTML editor element.
 *
 * @group Webform
 */
class WebformElementHtmlEditorTest extends WebformElementTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_html_editor'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create filters.
    $this->createFilters();
  }

  /**
   * Tests HTML Editor element.
   */
  public function testHtmlEditor() {
    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_element_html_editor');

    // Check required validation.
    $edit = [
      'webform_html_editor[value]' => '',
      'webform_html_editor_disable[value]' => '',
      'webform_html_editor_format[value][value]' => '',
      'webform_html_editor_codemirror[value]' => '',
    ];
    $this->postSubmission($webform, $edit);
    $this->assertRaw('webform_html_editor (default) field is required.');
    $this->assertRaw('webform_html_editor (disable) field is required.');
    $this->assertRaw('webform_html_editor (format) field is required.');
    $this->assertRaw('webform_html_editor_codemirror (none) field is required.');
    
    $this->drupalGet('webform/test_element_html_editor');

    // Check that HTML editor is enabled.
    $this->assertRaw('<textarea class="js-html-editor form-textarea required resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Check that CodeMirror is displayed when #format: FALSE.
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-codemirror-value" class="js-webform-codemirror webform-codemirror html required form-textarea resize-vertical" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-codemirror-value" name="webform_html_editor_codemirror[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Disable HTML editor.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[disabled]' => TRUE], t('Save configuration'));

    // Check that HTML editor is removed and replaced by CodeMirror HTML editor.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertNoRaw('<textarea class="js-html-editor form-textarea required resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html required form-textarea resize-vertical" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');

    // Enable HTML editor and text format.
    $edit = [
      'html_editor[disabled]' => FALSE,
      'html_editor[format]' => 'basic_html',
    ];
    $this->drupalPostForm('admin/structure/webform/config/elements', $edit, t('Save configuration'));

    // Check that Text format is disabled.
    $this->drupalGet('webform/test_element_html_editor');
    $this->assertNoRaw('<textarea class="js-html-editor form-textarea resize-vertical" data-drupal-selector="edit-webform-html-editor-value" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertNoRaw('<textarea data-drupal-selector="edit-webform-html-editor-value" class="js-webform-codemirror webform-codemirror html required form-textarea resize-vertical" required="required" aria-required="true" data-webform-codemirror-mode="text/html" id="edit-webform-html-editor-value" name="webform_html_editor[value]" rows="5" cols="60">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<textarea data-drupal-selector="edit-webform-html-editor-value-value" id="edit-webform-html-editor-value-value" name="webform_html_editor[value][value]" rows="5" cols="60" class="form-textarea required resize-vertical" required="required" aria-required="true">Hello &lt;b&gt;World!!!&lt;/b&gt;</textarea>');
    $this->assertRaw('<h4 class="label">Basic HTML</h4>');

    // Disable text format.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[format]' => ''], t('Save configuration'));

    // Check that tidy removed <p> tags.
    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p>');
    $this->assertEqual(\Drupal::service('renderer')->renderPlain($build), 'Some text');

    $build = WebformHtmlEditor::checkMarkup('<p class="other">Some text</p>');
    $this->assertEqual(\Drupal::service('renderer')->renderPlain($build), '<p class="other">Some text</p>');

    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p><p>More text</p>');
    $this->assertEqual(\Drupal::service('renderer')->renderPlain($build), '<p>Some text</p><p>More text</p>');

    // Disable HTML tidy.
    $this->drupalPostForm('admin/structure/webform/config/elements', ['html_editor[tidy]' => FALSE], t('Save configuration'));

    // Check that tidy is disabled.
    $build = WebformHtmlEditor::checkMarkup('<p>Some text</p>');
    $this->assertEqual(\Drupal::service('renderer')->renderPlain($build), '<p>Some text</p>');
  }

}
