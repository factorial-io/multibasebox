<?php

namespace Drupal\webform\Element;

use Drupal\webform\Twig\TwigExtension;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides an item to display computed webform submission values using Twig.
 *
 * @RenderElement("webform_computed_twig")
 */
class WebformComputedTwig extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public static function processValue(array $element, WebformSubmissionInterface $webform_submission) {
    $template = $element['#value'];
    $options = ['html' => (static::getMode($element) === static::MODE_HTML)];

    return TwigExtension::renderTwigTemplate($webform_submission, $template, $options);
  }

}
