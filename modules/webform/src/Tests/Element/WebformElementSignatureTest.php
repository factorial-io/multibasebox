<?php

namespace Drupal\webform\Tests\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for signature element.
 *
 * @group Webform
 */
class WebformElementSignatureTest extends WebformElementTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_signature'];

  /**
   * Test signature element.
   */
  public function testSignature() {
    $webform = Webform::load('test_element_signature');
    $this->drupalLogin($this->rootUser);

    // Check signature display.
    $this->drupalGet('webform/test_element_signature');
    $this->assertRaw('<input data-drupal-selector="edit-signature" aria-describedby="edit-signature--description" type="hidden" name="signature" value="" class="js-webform-signature form-webform-signature" />');
    $this->assertRaw('<input type="submit" name="op" value="Reset" class="button js-form-submit form-submit" />');
    $this->assertRaw('<canvas></canvas>');
    $this->assertRaw('</div>');
    $this->assertRaw('<div id="edit-signature--description" class="description">');
    $this->assertRaw('Sign above');

    // Check signature preview image.
    $this->postSubmissionTest($webform, [], t('Preview'));
    $this->assertRaw('webform/test_element_signature/signature/signature-');
    $this->assertRaw(' alt="Signature" class="webform-signature-image" />');

    // Check signature saved image.
    $sid = $this->postSubmissionTest($webform);
    $this->assertRaw("webform/test_element_signature/$sid/signature/signature-");
  }

}
