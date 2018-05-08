<?php

namespace Drupal\webform\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Defines the custom access control handler for the webform submission entities.
 */
class WebformSubmissionAccess {

  /**
   * Check whether a webform submissions' webform has wizard pages.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submisison.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkWizardPagesAccess(WebformSubmissionInterface $webform_submission) {
    return AccessResult::allowedIf($webform_submission->getWebform()->hasWizardPages());
  }

  /**
   * Check that webform submission has (email) messages and the user can update any webform submission.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public static function checkResendAccess(WebformSubmissionInterface $webform_submission, AccountInterface $account) {
    $webform = $webform_submission->getWebform();
    if ($webform->access('submission_update_any', $account) && $webform->hasMessageHandler()) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
