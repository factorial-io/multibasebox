<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides route responses for webform submissions.
 */
class WebformSubmissionController extends ControllerBase {

  /**
   * Toggle webform submission sticky.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the sticky icon.
   */
  public function sticky(WebformSubmissionInterface $webform_submission) {
    // Toggle sticky.
    $webform_submission->setSticky(!$webform_submission->isSticky())->save();

    // Get state.
    $state = $webform_submission->isSticky() ? 'on' : 'off';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand(
      '#webform-submission-' . $webform_submission->id() . '-sticky',
      new FormattableMarkup('<span class="webform-icon webform-icon-sticky webform-icon-sticky--@state"></span>', ['@state' => $state])
    ));
    return $response;
  }

  /**
   * Toggle webform submission locked.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that toggle the lock icon.
   */
  public function locked(WebformSubmissionInterface $webform_submission) {
    // Toggle locked.
    $webform_submission->setLocked(!$webform_submission->isLocked())->save();

    // Get state.
    $state = $webform_submission->isLocked() ? 'on' : 'off';

    // Get selector.
    $selector = '#webform-submission-' . $webform_submission->id() . '-locked';

    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand($selector, new FormattableMarkup('<span class="webform-icon webform-icon-lock webform-icon-locked--@state"></span>', ['@state' => $state])));
    $response->addCommand(new InvokeCommand($selector, 'trigger', ['blur']));
    return $response;
  }

}
