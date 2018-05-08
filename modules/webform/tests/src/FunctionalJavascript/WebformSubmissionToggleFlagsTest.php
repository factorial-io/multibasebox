<?php

namespace Drupal\Tests\webform\FunctionalJavascript;

use Drupal\Core\Serialization\Yaml;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestTrait;
use Drupal\webform\WebformInterface;

/**
 * Tests webform action javascript.
 *
 * @group webform_javascript
 */
class WebformSubmissionToggleFlagsTest extends JavascriptTestBase {

  use WebformTestTrait;

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [];

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform'];

  /**
   * Tests handler javascript.
   */
  public function testHandlerJavascript() {
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::create([
      'langcode' => 'en',
      'status' => WebformInterface::STATUS_OPEN,
      'id' => 'test_javascript',
      'title' => 'test_javascript',
      'elements' => Yaml::encode(['textfield' => ['#type' => 'textfield', '#title' => 'textfield']]),
    ]);
    $webform->save();

    $assert = $this->assertSession();
    $submit = $this->getWebformSubmitButtonLabel($webform);
    $this->drupalPostForm('webform/' . $webform->id(), [], $submit);
    $sid = $this->getLastSubmissionId($webform);
    $this->drupalLogin($this->createUser([
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]));
    $this->drupalGet('admin/structure/webform/manage/' . $webform->id() . '/results/submissions');
    $assert->statusCodeEquals(200);
    $assert->elementExists('css', "#webform-submission-$sid-sticky")->click();
    $assert->assertWaitOnAjaxRequest();
    $assert->elementExists('css', "#webform-submission-$sid-locked")->click();
    $assert->assertWaitOnAjaxRequest();
    // Reload the webform submission.
    \Drupal::entityTypeManager()->getStorage('webform_submission')->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertTrue($webform_submission->isSticky());
    $this->assertTrue($webform_submission->isLocked());
  }

}
