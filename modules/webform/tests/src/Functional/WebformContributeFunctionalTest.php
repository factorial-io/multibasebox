<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Webform contribute browser test.
 *
 * @group webform_browser
 */
class WebformContributeFunctionalTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['block', 'webform'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalLogin($this->rootUser);
    $this->drupalPlaceBlock('local_tasks_block');
  }

  /**
   * Test 'Contribute' section.
   */
  public function testContribute() {
    // Check that the 'Status report' includes 'Community information'.
    $this->drupalGet('/admin/structure/webform/contribute');
    $this->assertSession()->responseContains('Community information');
    $this->assertSession()->responseContains('When you <a href="https://register.drupal.org/user/register">create a Drupal.org account</a>, you gain access to a whole ecosystem of Drupal.org sites and services.');

    // Check that the 'Status report' includes jrockowitz's user information.
    $edit = [
      'account_type' => 'user',
      'user_id' => 'jrockowitz',
    ];
    $this->drupalPostForm('/admin/structure/webform/contribute/configure', $edit, t('Save'));
    $this->assertSession()->responseContains('Community information has been saved.');
    $this->assertSession()->responseContains('Community information');
    $this->assertSession()->responseNotContains('When you <a href="https://register.drupal.org/user/register">create a Drupal.org account</a>, you gain access to a whole ecosystem of Drupal.org sites and services.');
    $this->assertSession()->responseContains('<strong><a href="https://www.drupal.org/u/jrockowitz">Jacob Rockowitz</a></strong>');

    // Check that 'Community information' can be cleared.
    $this->drupalPostForm('/admin/structure/webform/contribute/configure', [], t('Clear'));
    $this->assertSession()->responseContains('Community information has been cleared.');
    $this->assertSession()->responseNotContains('<strong><a href="https://www.drupal.org/u/jrockowitz">Jacob Rockowitz</a></strong>');

    // Check that 'Contribute' local task is visible.
    $this->drupalGet('/admin/structure/webform');
    $this->assertSession()->linkExists('Contribute');

    // Check that 'Contribute' route is accessible.
    $this->drupalGet('/admin/structure/webform/contribute');
    $this->assertSession()->statusCodeEquals(200);

    // Check that 'Community information' can be disabled.
    $edit = ['ui[contribute_disabled]' => TRUE];
    $this->drupalPostForm('/admin/structure/webform/config/advanced', $edit, t('Save'));

    // Check that 'Contribute' local task is hidden.
    $this->drupalGet('/admin/structure/webform');
    $this->assertSession()->linkNotExists('Contribute');

    // Check that 'Contribute' route is removed.
    $this->drupalGet('/admin/structure/webform/contribute');
    $this->assertSession()->statusCodeEquals(404);
  }

}
