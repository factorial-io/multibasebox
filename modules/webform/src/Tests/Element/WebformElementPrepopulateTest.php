<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element prepopulate.
 *
 * @group Webform
 */
class WebformElementPrepopulateTest extends WebformElementTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['file', 'webform'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_prepopulate'];

  /**
   * Test element prepopulate.
   */
  public function testElementPrepopulate() {
    $webform = Webform::load('test_element_prepopulate');

    $files = $this->drupalGetTestFiles('text');

    // Check prepopulation of an element.
    $this->drupalGet('webform/test_element_prepopulate');
    $this->assertFieldByName('textfield', '');
    $this->assertFieldByName('textfield_prepopulate', '');
    $this->assertFieldByName('files[managed_file_prepopulate]', '');

    // Check 'textfield' can not be prepopulated.
    $this->drupalGet('webform/test_element_prepopulate', ['query' => ['textfield' => 'value']]);
    $this->assertNoFieldByName('textfield', 'value');

    // Check 'textfield_prepopulate' can be prepopulated.
    $this->drupalGet('webform/test_element_prepopulate', ['query' => ['textfield_prepopulate' => 'value']]);
    $this->assertFieldByName('textfield_prepopulate', 'value');

    // Check 'managed_file_prepopulate' can not be prepopulated.
    // The #prepopulate property is not available to managed file elements.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::getDefaultProperties
    $edit = [
      'files[managed_file_prepopulate]' => \Drupal::service('file_system')->realpath($files[0]->uri),
    ];
    $sid = $this->postSubmission($webform, $edit);
    $webform_submission = WebformSubmission::load($sid);
    $fid = $webform_submission->getElementData('managed_file_prepopulate');
    $this->drupalGet('webform/test_element_prepopulate', ['query' => ['managed_file_prepopulate' => $fid]]);
    $this->assertFieldByName('files[managed_file_prepopulate]', '');
  }

}
