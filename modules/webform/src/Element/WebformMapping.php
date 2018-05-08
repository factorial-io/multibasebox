<?php

namespace Drupal\webform\Element;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Provides a mapping element.
 *
 * @FormElement("webform_mapping")
 */
class WebformMapping extends FormElement {

  /**
   * Require all.
   */
  const REQUIRED_ALL = 'all';

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#input' => TRUE,
      '#process' => [
        [$class, 'processWebformMapping'],
        [$class, 'processAjaxForm'],
      ],
      '#theme_wrappers' => ['form_element'],
      '#required' => FALSE,
      '#source' => [],
      '#destination' => [],
      '#arrow' => '→',
    ];
  }

  /**
   * Processes a likert scale webform element.
   */
  public static function processWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    // Set translated default properties.
    $element += [
      '#source__title' => t('Source'),
      '#destination__title' => t('Destination'),
      '#arrow' => '→',
    ];

    $arrow = htmlentities($element['#arrow']);

    // Setup destination__type depending if #destination is defined.
    if (empty($element['#destination__type'])) {
      $element['#destination__type'] = (empty($element['#destination'])) ? 'textfield' : 'select';
    }

    // Set base destination element.
    $destination_element_base = [
      '#title_display' => 'invisible',
      '#required' => ($element['#required'] === self::REQUIRED_ALL) ? TRUE : FALSE,
    ];

    // Get base #destination__* properties.
    foreach ($element as $element_key => $element_value) {
      if (strpos($element_key, '#destination__') === 0 && !in_array($element_key, ['#destination__title'])) {
        $destination_element_base[str_replace('#destination__', '#', $element_key)] = $element_value;
      }
    }

    // Build header.
    $header = [
      ['data' => ['#markup' => $element['#source__title'] . ' ' . $arrow], 'width' => '50%'],
      ['data' => ['#markup' => $element['#destination__title']], 'width' => '50%'],
    ];

    // Build rows.
    $rows = [];
    foreach ($element['#source'] as $source_key => $source_title) {
      $default_value = (isset($element['#default_value'][$source_key])) ? $element['#default_value'][$source_key] : NULL;

      $destination_element = $destination_element_base + [
        '#title' => $source_title,
        '#required' => $element['#required'],
        '#default_value' => $default_value,
      ];

      // Apply #parents to destination element.
      if (isset($element['#parents'])) {
        $destination_element['#parents'] = array_merge($element['#parents'], [$source_key]);
      }

      switch ($element['#destination__type']) {
        case 'select':
        case 'webform_select_other':
          $destination_element += [
            '#empty_option' => t('- Select -'),
            '#options' => $element['#destination'],
          ];
          break;
      }

      $rows[$source_key] = [
        'source' => ['#markup' => $source_title . ' ' . $arrow],
        $source_key => $destination_element,
      ];
    }

    $element['table'] = [
      '#tree' => TRUE,
      '#type' => 'table',
      '#header' => $header,
      '#attributes' => [
        'class' => ['webform-mapping-table'],
      ],
    ] + $rows;

    // Build table element with selected properties.
    $properties = [
      '#states',
      '#sticky',
    ];
    $element['table'] += array_intersect_key($element, array_combine($properties, $properties));

    // Add validate callback.
    $element += ['#element_validate' => []];
    array_unshift($element['#element_validate'], [get_called_class(), 'validateWebformMapping']);

    if (!empty($element['#states'])) {
      webform_process_states($element, '#wrapper_attributes');
    }

    return $element;
  }

  /**
   * Validates a mapping element.
   */
  public static function validateWebformMapping(&$element, FormStateInterface $form_state, &$complete_form) {
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);
    $value = array_filter($value);

    // Note: Not validating REQUIRED_ALL because each destination element is
    // already required.
    $has_access = (!isset($element['#access']) || $element['#access'] === TRUE);
    if ($element['#required'] && $element['#required'] !== self::REQUIRED_ALL && empty($value) && $has_access) {
      WebformElementHelper::setRequiredError($element, $form_state);
    }

    $element['#value'] = $value;
    $form_state->setValueForElement($element, $value);
  }

}
