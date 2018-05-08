<?php

namespace Drupal\webform_image_select;

use Drupal\Core\Entity\EntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\webform\Form\WebformDialogFormTrait;

/**
 * Provides a delete webform images select images form.
 */
class WebformImageSelectImagesDeleteForm extends EntityDeleteForm {

  use WebformDialogFormTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    /** @var \Drupal\webform\WebformOptionsInterface $webform_options */
    $webform_options = $this->entity;

    /** @var \Drupal\webform_image_select\WebformImageSelectImagesStorageInterface $webform_images_storage */
    $webform_images_storage = $this->entityTypeManager->getStorage('webform_image_select_images');

    // Display warning that options is used by webforms.
    $t_args = ['%title' => $webform_options->label()];
    if ($used_by_webforms = $webform_images_storage->getUsedByWebforms($webform_options)) {
      $form['used_by_composite_elements'] = [
        '#type' => 'webform_message',
        '#message_message' => [
          '#theme' => 'item_list',
          '#title' => $this->t('%title is used by the below webform(s).', $t_args),
          '#items' => $used_by_webforms,
        ],
        '#message_type' => 'warning',
        '#weight' => -100,
      ];
    }

    $form['confirm'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Yes, I want to delete these webform images.'),
      '#required' => TRUE,
      '#weight' => 10,
    ];

    return $this->buildDialogConfirmForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    return Url::fromRoute('entity.webform_image_select_images.collection');
  }

}
