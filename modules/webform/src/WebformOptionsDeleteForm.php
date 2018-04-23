<?php

namespace Drupal\webform;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformDialogFormTrait;

/**
 * Provides a delete webform options form.
 */
class WebformOptionsDeleteForm extends EntityDeleteForm {

  use WebformDialogFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    /** @var \Drupal\webform\WebformOptionsStorageInterface $webform_options_storage */
    $webform_options_storage = $this->entityTypeManager->getStorage('webform_options');

    // Display warning that options is used by composite elements
    // and/or webforms.
    $t_args = ['%title' => $webform_options->label()];
    $message = [];
    if ($used_by_elements = $webform_options_storage->getUsedByCompositeElements($webform_options)) {
      $message['elements'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('%title is used by the below composite element(s).', $t_args),
        '#items' => $used_by_elements,
      ];
    }
    if ($used_by_webforms = $webform_options_storage->getUsedByWebforms($webform_options)) {
      $message['webform'] = [
        '#theme' => 'item_list',
        '#title' => $this->t('%title is used by the below webform(s).', $t_args),
        '#items' => $used_by_webforms,
      ];
    }
    if ($message) {
      $form['used_by_composite_elements'] = [
        '#type' => 'webform_message',
        '#message_message' => $message,
        '#message_type' => 'warning',
        '#weight' => -100,
      ];
    }

    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to delete these webform options.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return Url::fromRoute('entity.webform_options.collection');
  }

}
