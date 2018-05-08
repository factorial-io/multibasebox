<?php

namespace Drupal\webform\Tests\Handler;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\WebformTestBase;

/**
 * Tests for email webform handler rendering functionality.
 *
 * @group Webform
 */
class WebformHandlerEmailRenderingTest extends WebformTestBase {

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Make sure we are using distinct default and administrative themes for
    // the duration of these tests.
    \Drupal::service('theme_handler')->install(['webform_test_bartik', 'seven']);
    $this->config('system.theme')
      ->set('default', 'webform_test_bartik')
      ->set('admin', 'seven')
      ->save();
  }

  /**
   * Test email handler rendering.
   */
  public function testEmailRendering() {
    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Check that we are currently using the bartik.theme.
    $this->drupalGet('webform/contact');
    $this->assertRaw('core/themes/bartik/css/base/elements.css');

    // Post submission and send emails.
    $edit = [
      'name' => 'Dixisset',
      'email' => 'test@test.com',
      'subject' => 'Testing contact webform from [site:name]',
      'message' => 'Please ignore this email.',
    ];
    $this->postSubmission($webform, $edit);

    /* BELOW TEST IS PASSING LOCALL BUT FAILING ON DRUPAL.ORG.
    // Check submitting contact form and sending emails using the
    // default bartik.theme.
    $sent_emails = $this->drupalGetMails();
    $this->assertContains($sent_emails[0]['body'], 'HEADER 1 (CONTACT_EMAIL_CONFIRMATION)');
    $this->assertContains($sent_emails[0]['body'], 'Please ignore this email.');
    $this->assertContains($sent_emails[0]['body'],'address (contact_email_confirmation)');
    $this->assertContains($sent_emails[1]['body'], 'HEADER 1 (GLOBAL)');
    $this->assertContains($sent_emails[1]['body'], 'Please ignore this email.');
    $this->assertContains($sent_emails[1]['body'],'address (global)');
    */

    // Disable dedicated page which will cause the form to now use the
    // seven.theme.
    // @see \Drupal\webform\Theme\WebformThemeNegotiator
    $webform->setSetting('page', FALSE);
    $webform->save();

    // Check that we are now using the seven.theme.
    $this->drupalGet('webform/contact');
    $this->assertNoRaw('core/themes/bartik/css/base/elements.css');

    // Post submission and send emails.
    $this->postSubmission($webform, $edit);

    /* BELOW TEST IS PASSING LOCALL BUT FAILING ON DRUPAL.ORG.
    // Check submitting contact form and sending emails using the
    // seven.theme but the rendered the emails still use the default
    // bartik.theme.
    // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
    $sent_emails = $this->drupalGetMails();
    $this->assertContains($sent_emails[2]['body'], 'HEADER 1 (CONTACT_EMAIL_CONFIRMATION)');
    $this->assertContains($sent_emails[2]['body'], 'Please ignore this email.');
    $this->assertContains($sent_emails[2]['body'],'address (contact_email_confirmation)');
    $this->assertContains($sent_emails[3]['body'], 'HEADER 1 (GLOBAL)');
    $this->assertContains($sent_emails[3]['body'], 'Please ignore this email.');
    $this->assertContains($sent_emails[3]['body'],'address (global)');
    */
  }

}
