<?php

namespace Drupal\webform\Tests\Composite;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Tests\WebformTestBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Tests for webform submission webform element custom #format support.
 *
 * @group Webform
 */
class WebformCompositeFormatTest extends WebformTestBase {

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
  protected static $testWebforms = ['test_composite_format', 'test_composite_format_multiple'];

  /**
   * Tests element format.
   */
  public function testFormat() {

    /**************************************************************************/
    /* Format composite element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_composite_format');
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);

    // Check composite elements item formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');
    $elements = [
      'Text format (Plain text)' => '<p>&lt;p&gt;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Negat esse eam, inquit, propter se expetendam. Primum Theophrasti, Strato, physicum se voluit; Id mihi magnum videtur. Itaque mihi non satis videmini considerare quod iter sit naturae quaeque progressio. Quare hoc videndum est, possitne nobis hoc ratio philosophorum dare. Est enim tanti philosophi tamque nobilis audacter sua decreta defendere.&lt;/p&gt;</p>',
      'Likert (Value)' => '<div class="item-list"><ul><li><b>Please answer question 1?:</b> 1</li><li><b>How about now answering question 2?:</b> 1</li><li><b>Finally, here is question 3?:</b> 1</li></ul></div>',
      'Likert (Raw value)' => '<div class="item-list"><ul><li><b>q1:</b> 1</li><li><b>q2:</b> 1</li><li><b>q3:</b> 1</li></ul></div>',
      'Likert (List)' => '<div class="item-list"><ul><li><b>Please answer question 1?:</b> 1</li><li><b>How about now answering question 2?:</b> 1</li><li><b>Finally, here is question 3?:</b> 1</li></ul></div>',
      'Address (Value)' => '10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan<br />',
      'Address (Raw value)' => '<div class="item-list"><ul><li><b>address:</b> 10 Main Street</li><li><b>address_2:</b> 10 Main Street</li><li><b>city:</b> Springfield</li><li><b>state_province:</b> Alabama</li><li><b>postal_code:</b> Loremipsum</li><li><b>country:</b> Afghanistan</li></ul></div><br /><br />',
      'Address (List)' => '<div class="item-list"><ul><li><b>Address:</b> 10 Main Street</li><li><b>Address 2:</b> 10 Main Street</li><li><b>City/Town:</b> Springfield</li><li><b>State/Province:</b> Alabama</li><li><b>Zip/Postal Code:</b> Loremipsum</li><li><b>Country:</b> Afghanistan</li></ul></div><br /><br />',
      'Link (Value)' => '<a href="http://example.com">Loremipsum</a>',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains($body, '<b>' . $label . '</b><br />' . $value, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check composite elements formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'Link (Value): Loremipsum (http://example.com)',
      'Address (Value):
10 Main Street
10 Main Street
Springfield, Alabama. Loremipsum
Afghanistan',
      'Address (Raw value):
address: 10 Main Street
address_2: 10 Main Street
city: Springfield
state_province: Alabama
postal_code: Loremipsum
country: Afghanistan',
      'Address (List):
Address: 10 Main Street
Address 2: 10 Main Street
City/Town: Springfield
State/Province: Alabama
Zip/Postal Code: Loremipsum
Country: Afghanistan',
      'Likert (Value):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
      'Likert (Raw value):
q1: 1
q2: 1
q3: 1',
      'Likert (List):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
      'Likert (Table):
Please answer question 1?: 1
How about now answering question 2?: 1
Finally, here is question 3?: 1',
    ];
    foreach ($elements as $value) {
      $this->assertContains($body, $value, new FormattableMarkup('Found @value', ['@value' => $value]));
    }

    /**************************************************************************/
    /* Format composite multiple element as HTML and text */
    /**************************************************************************/

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_composite_format_multiple');
    $sid = $this->postSubmission($webform);
    $submission = WebformSubmission::load($sid);

    // Check composite elements item formatted as HTML.
    $body = $this->getMessageBody($submission, 'email_html');

    // Remove all spaces between tags to that we can easily check the output.
    $body = preg_replace('/>\s+</ims', '><', $body);
    $body = str_replace('<b>', PHP_EOL . '<b>', $body);
    $this->debug($body);

    $elements = [
      'Address (Ordered list)' => '<div class="item-list"><ol><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li></ol></div>',
      'Address (Unordered list)' => '<div class="item-list"><ul><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li><li>10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan</li></ul></div>',
      'Address (Horizontal rule)' => '10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan<hr class="webform-horizontal-rule" />10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan<hr class="webform-horizontal-rule" />10 Main Street<br />10 Main Street<br />Springfield, Alabama. Loremipsum<br />Afghanistan',
      'Address (Table)' => '<table width="100%" cellspacing="0" cellpadding="5" border="1" class="responsive-enabled" data-striping="1"><thead><tr><th bgcolor="#eee">Address</th><th bgcolor="#eee">Address 2</th><th bgcolor="#eee">City/Town</th><th bgcolor="#eee">State/Province</th><th bgcolor="#eee">Zip/Postal Code</th><th bgcolor="#eee">Country</th></tr></thead><tbody><tr class="odd"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>Loremipsum</td><td>Afghanistan</td></tr><tr class="even"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>Loremipsum</td><td>Afghanistan</td></tr><tr class="odd"><td>10 Main Street</td><td>10 Main Street</td><td>Springfield</td><td>Alabama</td><td>Loremipsum</td><td>Afghanistan</td></tr></tbody></table>',
    ];
    foreach ($elements as $label => $value) {
      $this->assertContains($body, '<b>' . $label . '</b><br />' . $value, new FormattableMarkup('Found @label: @value', ['@label' => $label, '@value' => $value]));
    }

    // Check composite elements formatted as text.
    $body = $this->getMessageBody($submission, 'email_text');
    $elements = [
      'Address (Ordered list):
1. 10 Main Street
   10 Main Street
   Springfield, Alabama. Loremipsum
   Afghanistan
2. 10 Main Street
   10 Main Street
   Springfield, Alabama. Loremipsum
   Afghanistan
3. 10 Main Street
   10 Main Street
   Springfield, Alabama. Loremipsum
   Afghanistan',
      'Address (Unordered list):
- 10 Main Street
  10 Main Street
  Springfield, Alabama. Loremipsum
  Afghanistan
- 10 Main Street
  10 Main Street
  Springfield, Alabama. Loremipsum
  Afghanistan
- 10 Main Street
  10 Main Street
  Springfield, Alabama. Loremipsum
  Afghanistan',
      'Address (Horizontal rule):
10 Main Street
10 Main Street
Springfield, Alabama. Loremipsum
Afghanistan
---
10 Main Street
10 Main Street
Springfield, Alabama. Loremipsum
Afghanistan
---
10 Main Street
10 Main Street
Springfield, Alabama. Loremipsum
Afghanistan',
    ];
    foreach ($elements as $value) {
      $this->assertContains($body, $value, new FormattableMarkup('Found @value', ['@value' => $value]));
    }
  }

  /**
   * Get webform email message body for a webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   A webform submission.
   * @param string $handler_id
   *   The webform email handler id.
   *
   * @return string
   *   The webform email message body for a webform submission.
   */
  protected function getMessageBody(WebformSubmissionInterface $submission, $handler_id = 'email_html') {
    /** @var \Drupal\webform\Plugin\WebformHandlerMessageInterface $message_handler */
    $message_handler = $submission->getWebform()->getHandler($handler_id);
    $message = $message_handler->getMessage($submission);
    $body = (string) $message['body'];
    $this->verbose($body);
    return $body;
  }

}
