<?php

namespace Drupal\webform_image_select\Tests;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Tests\Element\WebformElementTestBase;

/**
 * Tests for webform image select element.
 *
 * @group Webform
 */
class WebformImageSelectElementTest extends WebformElementTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['webform', 'webform_image_select', 'webform_image_select_test'];

  /**
   * Test webform image select element.
   */
  public function testImageSelect() {
    $this->drupalGet('webform/test_element_image_select');

    // Check rendering of image select with required.
    $this->assertRaw('<select data-drupal-selector="edit-image-select-default" data-images="{&quot;kitten_1&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;kitten_2&quot;:{&quot;text&quot;:&quot;Cute Kitten 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;kitten_3&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;kitten_4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select required" id="edit-image-select-default" name="image_select_default" required="required" aria-required="true">');

    // Check rendering of image select with limit.
    $this->assertRaW('<select data-drupal-selector="edit-image-select-limit" data-limit="2" data-images="{&quot;kitten_1&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;kitten_2&quot;:{&quot;text&quot;:&quot;Cute Kitten 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;kitten_3&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;kitten_4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select" multiple="multiple" name="image_select_limit[]" id="edit-image-select-limit">');

    // Check rendering of image select with HTML markup and XSS test.
    $this->assertRaW('<select data-drupal-selector="edit-image-select-html" data-show-label="data-show-label" data-images="{&quot;\u003C1\u003E&quot;:{&quot;text&quot;:&quot;Cute Kitten 1&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/220\/200&quot;},&quot;\u00222\u0022&quot;:{&quot;text&quot;:&quot;Cute \u003Cem\u003EKitten\u003C\/em\u003E 2&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/180\/200&quot;},&quot;\u00263&quot;:{&quot;text&quot;:&quot;Cute Kitten 3&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/130\/200&quot;},&quot;4&quot;:{&quot;text&quot;:&quot;Cute Kitten 4 alert(\u0022XSS\u0022);&quot;,&quot;src&quot;:&quot;http:\/\/placekitten.com\/270\/200&quot;}}" class="webform-image-select js-webform-image-select form-select" id="edit-image-select-html" name="image_select_html"><option value="" selected="selected">- None -</option><option value="&lt;1&gt;">');

    // Check preview.
    $webform = Webform::load('test_element_image_select');
    $edit = [
      'image_select_default' => 'kitten_1',
    ];
    $this->postSubmission($webform, $edit, t('Preview'));
    $this->assertRaw('<figure style="display: inline-block; margin: 0 6px 6px 0; padding: 6px; border: 1px solid #ddd;width: 220px"><img src="http://placekitten.com/220/200" width="220" height="200" alt="Cute Kitten 1" title="Cute Kitten 1" />');

  }

}
