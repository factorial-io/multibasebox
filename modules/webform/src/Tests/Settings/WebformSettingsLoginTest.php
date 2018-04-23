<?php

namespace Drupal\webform\Tests\Settings;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for login redirect webform and submissions.
 *
 * @group Webform
 */
class WebformSettingsLoginTest extends WebformTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_login'];

  /**
   * Tests webform login setting.
   */
  public function testLogin() {
    // Create a webform submission.
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('test_form_login');
    $sid = $this->postSubmission($webform);
    $this->drupalLogout();

    // Check form message is displayed.
    $this->drupalGet('admin/structure/webform/manage/test_form_login');
    $this->assertRaw('Please login to access <b>Test: Webform: Login</b>.');

    // Check submission message is displayed.
    $this->drupalGet("admin/structure/webform/manage/test_form_login/submission/$sid");
    $this->assertRaw("Please login to access <b>Test: Webform: Login: Submission #$sid</b>.");

    // Disable login message.
    $webform = Webform::load('test_form_login');
    $webform->setSetting('form_login', FALSE);
    $webform->setSetting('submission_login', FALSE);
    $webform->save();

    // Check submission message is not displayed.
    $this->drupalGet('admin/structure/webform/manage/test_form_login');
    $this->assertNoRaw('Please login to access <b>Test: Webform: Login</b>.');

    // Check form message is not displayed.
    $this->drupalGet("admin/structure/webform/manage/test_form_login/submission/$sid");
    $this->assertNoRaw("Please login to access <b>Test: Webform: Login: Submission #$sid</b>.");
  }

}
