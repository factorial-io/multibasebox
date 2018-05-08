<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Url;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Defines a class to build a listing of webform options entities.
 *
 * @see \Drupal\webform\Entity\WebformOption
 */
class WebformOptionsListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = [];

    // Display info.
    if ($total = $this->getStorage()->getQuery()->count()->execute()) {
      $build['info'] = [
        '#markup' => $this->formatPlural($total, '@total option', '@total options', ['@total' => $total]),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build += parent::render();

    $build['#attached']['library'][] = 'webform/webform.admin.dialog';

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Label');
    $header['category'] = $this->t('Category');
    $header['likert'] = $this->t('Likert');
    $header['alter'] = [
      'data' => $this->t('Altered'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['options'] = [
      'data' => $this->t('Options'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header['used_by'] = [
      'data' => $this->t('Used by Webforms / Composites'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\webform\WebformOptionsInterface $entity */
    $row['label'] = $entity->toLink($entity->label(), 'edit-form');
    $row['category'] = $entity->get('category');
    $row['likert'] = $entity->isLikert() ? $this->t('Yes') : $this->t('No');
    $row['alter'] = $entity->hasAlterHooks() ? $this->t('Yes') : $this->t('No');
    $row['options'] = $this->buildOptions($entity);
    $row['used_by'] = $this->buildUsedBy($entity);
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('duplicate')) {
      $operations['duplicate'] = [
        'title' => $this->t('Duplicate'),
        'weight' => 23,
        'url' => Url::fromRoute('entity.webform_options.duplicate_form', ['webform_options' => $entity->id()]),
      ];
    }
    if (isset($operations['delete'])) {
      $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
    }
    return $operations;
  }

  /**
   * Build list of webforms and composite elements that the webform options is used by.
   *
   * @param \Drupal\webform\WebformOptionsInterface $webform_options
   *   A webform options entity
   *
   * @return array
   *   Table data containing list of webforms and composite elements that the
   *   webform options is used by.
   */
  protected function buildUsedBy(WebformOptionsInterface $webform_options) {
    $links = [];
    $webforms = $this->getStorage()->getUsedByWebforms($webform_options);
    foreach ($webforms as $id => $title) {
      $links[] = [
        '#type' => 'link',
        '#title' => $title,
        '#url' => Url::fromRoute('entity.webform.canonical', ['webform' => $id]),
        '#suffix' => '</br>'
      ];
    }
    $elements = $this->getStorage()->getUsedByCompositeElements($webform_options);
    foreach ($elements as $id => $title) {
      $links[] = [
        '#markup' => $title,
        '#suffix' => '</br>'
      ];
    }
    return [
      'nowrap' => TRUE,
      'data' => $links,
    ];
  }

  /**
   * Build list of webform options.
   *
   * @param \Drupal\webform\WebformOptionsInterface $webform_options
   *   A webform options entity
   *
   * @return string
   *   Semi-colon delimited list of webform options.
   */
  protected function buildOptions(WebformOptionsInterface $webform_options) {
    $element = ['#options' => $webform_options->id()];
    $options = WebformOptions::getElementOptions($element);
    $options = OptGroup::flattenOptions($options);
    foreach ($options as $key => &$value) {
      if ($key != $value) {
        $value .= ' (' . $key . ')';
      }
    }
    return implode('; ', array_slice($options, 0, 12)) . (count($options) > 12 ? '; ...' : '');
  }

}
