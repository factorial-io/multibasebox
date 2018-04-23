<?php

namespace Drupal\webform;

use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an interface defining a webform conditions (#states) validator.
 */
interface WebformSubmissionConditionsValidatorInterface {

  /**
   * Apply form #states to visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function buildForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate form #states for visible elements.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state);

  /**
   * Validate state with conditions.
   *
   * @param string $state
   *   A state.
   * @param array $conditions
   *   An associative array containing conditions.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if conditions validate. NULL if conditions can't be processed.
   */
  public function validateState($state, array $conditions, WebformSubmissionInterface $webform_submission);

  /**
   * Validate #state conditions.
   *
   * @param array $conditions
   *   An associative array containing conditions.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool|null
   *   TRUE if conditions validate. NULL if conditions can't be processed.
   *
   * @see drupal_process_states()
   */
  public function validateConditions(array $conditions, WebformSubmissionInterface $webform_submission);

  /**
   * Determine if an element is visible.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   *
   * @return bool
   *   TRUE if the element is visible.
   */
  public function isElementVisible(array $element, WebformSubmissionInterface $webform_submission);

}
