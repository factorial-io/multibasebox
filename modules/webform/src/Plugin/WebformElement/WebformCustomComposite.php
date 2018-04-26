<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Provides a custom composite element.
 *
 * @WebformElement(
 *   id = "webform_custom_composite",
 *   label = @Translation("Custom composite"),
 *   description = @Translation("Provides a form element to create custom composites using a grid/table layout."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformCustomComposite extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $properties = $this->getDefaultMultipleProperties() + parent::getDefaultProperties();
    $properties['title_display'] = '';
    $properties['element'] = [];
    unset($properties['flexbox']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultMultipleProperties() {
    $properties = [
      'multiple' => TRUE,
      'multiple__header' => TRUE,
    ] + parent::getDefaultMultipleProperties();
    return $properties;

  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    // WebformComposite extends the WebformMultiple and will always store
    // multiple values.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    // Set cardinality.
    if (isset($element['#multiple'])) {
      $element['#cardinality'] = ($element['#multiple'] === FALSE) ? 1 : $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->getDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property, 'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = (isset($element["#$multiple_property"])) ? $element["#$multiple_property"] : $multiple_value;
      }
    }

    // Default to displaying table header.
    $element += ['#header' => TRUE];

    // If header label is defined use it for the #header.
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Transfer '#{composite_key}_{property}' from main element to composite
    // element.
    foreach ($element['#element'] as $composite_key => $composite_element) {
      foreach ($element as $property_key => $property_value) {
        if (strpos($property_key, '#' . $composite_key . '__') === 0) {
          $composite_property_key = str_replace('#' . $composite_key . '__', '#', $property_key);
          $element['#element'][$composite_key][$composite_property_key] = $property_value;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareMultipleWrapper(array &$element) {
    // Don't set multiple wrapper since 'webform_composite' extends
    // 'webform_multiple'.
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Always to should multiple element settings since WebformComposite
    // extends WebformMultiple.
    unset($form['multiple']['#states']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildCompositeElementsTable() {
    return [
      '#type' => 'webform_element_composite',
      '#title' => $this->t('Elements'),
      '#title_display' => $this->t('Invisible'),
    ];
  }

  /****************************************************************************/
  // Preview method.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preview() {
    return [
      '#type' => $this->getTypeName(),
      '#title' => $this->getPluginLabel(),
      '#element' => [
        'name' => [
          '#type' => 'textfield',
          '#title' => 'Name',
          '#title_display' => 'invisible',
        ],
        'gender' => [
          '#type' => 'select',
          '#title' => 'Gender',
          '#title_display' => 'invisible',
          '#options' => [
            'Male' => $this->t('Male'),
            'Female' => $this->t('Female'),
          ],
        ],
      ],
    ];
  }

  /****************************************************************************/
  // Test methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    /** @var \Drupal\webform\WebformSubmissionGenerateInterface $generate */
    $generate = \Drupal::service('webform_submission.generate');

    $composite_elements = $element['#element'];

    // Initialize, prepare, and populate composite sub-element.
    foreach ($composite_elements as $composite_key => $composite_element) {
      $this->elementManager->initializeElement($composite_element);
      $composite_elements[$composite_key] = $composite_element;
    }

    $values = [];
    for ($i = 1; $i <= 3; $i++) {
      $value = [];
      foreach ($composite_elements as $composite_key => $composite_element) {
        $value[$composite_key] = $generate->getTestValue($webform, $composite_key, $composite_element, $options);
      }
      $values[] = $value;
    }
    return $values;
  }

  /****************************************************************************/
  // Composite element methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initializeCompositeElements(array &$element) {
    $element['#webform_composite_elements'] = [];
    foreach ($element['#element'] as $composite_key => $composite_element) {
      // Initialize composite sub-element.
      $this->elementManager->initializeElement($composite_element);
      $element['#webform_composite_elements'][$composite_key] = $composite_element;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCompositeElements() {
    // Return empty array since composite (sub) elements are custom.
    return [];
  }

}
