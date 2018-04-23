<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Twig\TwigExtension;

/**
 * Provides a 'webform_computed_twig' element.
 *
 * @WebformElement(
 *   id = "webform_computed_twig",
 *   label = @Translation("Computed Twig"),
 *   description = @Translation("Provides an item to display computed webform submission values using Twig."),
 *   category = @Translation("Computed Elements"),
 * )
 */
class WebformComputedTwig extends WebformComputedBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['computed']['help'] = TwigExtension::buildTwigHelp();
    $form['computed']['value']['#mode'] = 'twig';

    // Set #access so that help is always visible.
    WebformElementHelper::setPropertyRecursive($form['computed']['help'], '#access', TRUE);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    // Validate Twig markup with no context.
    try {
      $build = [
        '#type' => 'inline_template',
        '#template' => $form_state->getValue('value'),
        '#context' => [],
      ];
      \Drupal::service('renderer')->renderPlain($build);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('markup', [
        'message' => ['#markup' => $this->t('Failed to render computed Twig value due to error.'), '#suffix' => '<br /><br />'],
        'error' => ['#markup' => Html::escape($exception->getMessage()), '#prefix' => '<pre>', '#suffix' => '</pre>'],
      ]);
    }
  }

}
