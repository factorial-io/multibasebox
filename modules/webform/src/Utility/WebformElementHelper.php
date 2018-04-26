<?php

namespace Drupal\webform\Utility;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Render\Element;
use Drupal\Core\Template\Attribute;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;

/**
 * Helper class webform element methods.
 */
class WebformElementHelper {

  /**
   * Ignored element properties.
   *
   * @var array
   */
  public static $ignoredProperties = [
    // Properties that will allow code injection.
    '#allowed_tags' => '#allowed_tags',
      // Properties that will break webform data handling.
    '#tree' => '#tree',
    '#array_parents' => '#array_parents',
    '#parents' => '#parents',
    // Properties that will cause unpredictable rendering.
    '#weight' => '#weight',
    // Callbacks are blocked to prevent unwanted code executions.
    '#after_build' => '#after_build',
    '#element_validate' => '#element_validate',
    '#post_render' => '#post_render',
    '#pre_render' => '#pre_render',
    '#process' => '#process',
    '#submit' => '#submit',
    '#validate' => '#validate',
    '#value_callback' => '#value_callback',
  ];

  /**
   * Ignored element sub properties used by composite elements.
   *
   * @var array
   */
  public static $ignoredSubProperties = [
    // Properties that will allow code injection.
    '#allowed_tags' => '#allowed_tags',
    // Properties that will break webform data handling.
    '#tree' => '#tree',
    '#array_parents' => '#array_parents',
    '#parents' => '#parents',
    // Callbacks are blocked to prevent unwanted code executions.
    '#after_build' => '#after_build',
    '#element_validate' => '#element_validate',
    '#post_render' => '#post_render',
    '#pre_render' => '#pre_render',
    '#process' => '#process',
    '#submit' => '#submit',
    '#validate' => '#validate',
    '#value_callback' => '#value_callback',
  ];

  /**
   * Regular expression used to determine if sub-element property should be ignored.
   *
   * @var string
   */
  protected static $ignoredSubPropertiesRegExp;

  /**
   * Determine if a webform element's title is displayed.
   *
   * @param array $element
   *   A webform element.
   *
   * @return bool
   *   TRUE if a webform element's title is displayed.
   */
  public static function isTitleDisplayed(array $element) {
    return (!empty($element['#title']) && (empty($element['#title_display']) || !in_array($element['#title_display'], ['invisible', 'attribute']))) ? TRUE : FALSE;
  }

  /**
   * Get an associative array containing a render element's properties.
   *
   * @param array $element
   *   A render element.
   *
   * @return array
   *   An associative array containing a render element's properties.
   */
  public static function getProperties(array $element) {
    $properties = [];
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        $properties[$key] = $value;
      }
    }
    return $properties;
  }

  /**
   * Remove all properties from a render element.
   *
   * @param array $element
   *   A render element.
   *
   * @return array
   *   A render element with no properties.
   */
  public static function removeProperties(array $element) {
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        unset($element[$key]);
      }
    }
    return $element;
  }

  /**
   * Set a property on all elements and sub-elements.
   *
   * @param array $element
   *   A render element.
   * @param string $property_key
   *   The property key.
   * @param mixed $property_value
   *   The property value.
   */
  public static function setPropertyRecursive(array &$element, $property_key, $property_value) {
    $element[$property_key] = $property_value;
    foreach (Element::children($element) as $key) {
      self::setPropertyRecursive($element[$key], $property_key, $property_value);
    }
  }

  /**
   * Enhance select menu with the Select2 or the Chosen library.
   *
   * Please Note: Select2 is preferred library for Webform administrative
   * forms.
   *
   * @param array $element
   *   A select element.
   * @param bool $library
   *   Flag to automatically detect and apply library.
   *
   * @return array
   *   The select element with Select2 or Chosen class and library attached.
   */
  public static function enhanceSelect(array &$element, $library = FALSE) {
    // If automatic is FALSE, look at the element's #select2 and #chosen
    // property.
    if (!$library) {
      if (isset($element['#select2'])) {
        $library = 'select2';
      }
      elseif (isset($element['#chosen'])) {
        $library = 'chosen';
      }
    }

    if ($library === FALSE) {
      return $element;
    }

    /** @var \Drupal\webform\WebformLibrariesManagerInterface $libaries_manager */
    $libaries_manager = \Drupal::service('webform.libraries_manager');

    // Add select2 library and classes.
    if (($library === TRUE || $library === 'select2') && $libaries_manager->isIncluded('jquery.select2')) {
      $element['#attached']['library'][] = 'webform/webform.element.select2';
      $element['#attributes']['class'][] = 'js-webform-select2';
      $element['#attributes']['class'][] = 'webform-select2';
    }
    // Add chosen library and classes.
    elseif (($library === TRUE || $library === 'chosen') && $libaries_manager->isIncluded('jquery.chosen')) {
      $element['#attached']['library'][] = 'webform/webform.element.chosen';
      $element['#attributes']['class'][] = 'js-webform-chosen';
      $element['#attributes']['class'][] = 'webform-chosen';
    }

    return $element;
  }

  /**
   * Fix webform element #states handling.
   *
   * @param array $element
   *   A webform element that is missing the 'data-drupal-states' attribute.
   */
  public static function fixStatesWrapper(array &$element) {
    if (empty($element['#states'])) {
      return;
    }

    $attributes = [];
    $attributes['class'][] = 'js-form-wrapper';
    $attributes['data-drupal-states'] = Json::encode($element['#states']);

    $element += ['#prefix' => '', '#suffix' => ''];

    // ISSUE: JSON is being corrupted when the prefix is rendered.
    // $element['#prefix'] = '<div ' . new Attribute($attributes) . '>' . $element['#prefix'];
    // WORKAROUND: Safely set filtered #prefix to FormattableMarkup.
    $allowed_tags = isset($element['#allowed_tags']) ? $element['#allowed_tags'] : Xss::getHtmlTagList();
    $element['#prefix'] = Markup::create('<div' . new Attribute($attributes) . '>' . Xss::filter($element['#prefix'], $allowed_tags));
    $element['#suffix'] = $element['#suffix'] . '</div>';

    // Attach library.
    $element['#attached']['library'][] = 'core/drupal.states';

    // Copy #states to #_webform_states property which can be used by the
    // WebformSubmissionConditionsValidator.
    // @see \Drupal\webform\WebformSubmissionConditionsValidator
    $element['#_webform_states'] = $element['#states'];

    // Remove #states property to prevent nesting.
    unset($element['#states']);
  }

  /**
   * Get ignored properties from a webform element.
   *
   * @param array $element
   *   A webform element.
   *
   * @return array
   *   An array of ignored properties.
   */
  public static function getIgnoredProperties(array $element) {
    $ignored_properties = [];
    foreach ($element as $key => $value) {
      if (Element::property($key)) {
        if (self::isIgnoredProperty($key)) {
          $ignored_properties[$key] = $key;
        }
        elseif ($key == '#element' && is_array($value) && isset($element['#type']) && $element['#type'] === 'webform_composite') {
          foreach ($value as $composite_value) {

            // Multiple sub composite elements are not supported.
            if (isset($composite_value['#multiple'])) {
              $ignored_properties['#multiple'] = t('Custom composite sub elements do not support elements with multiple values.');
            }

            // Check that sub composite element type is supported.
            if (isset($composite_value['#type']) && !WebformCompositeBase::isSupportedElementType($composite_value['#type'])) {
              $composite_type = $composite_value['#type'];
              $ignored_properties["composite.$composite_type"] = t('Custom composite elements do not support the %type element.', ['%type' => $composite_type]);
            }

            $ignored_properties += self::getIgnoredProperties($composite_value);
          }
        }
      }
      elseif (is_array($value)) {
        $ignored_properties += self::getIgnoredProperties($value);
      }
    }
    return $ignored_properties;
  }

  /**
   * Remove ignored properties from an element.
   *
   * @param array $element
   *   A webform element.
   *
   * @return array
   *   A webform element with ignored properties removed.
   */
  public static function removeIgnoredProperties(array $element) {
    foreach ($element as $key => $value) {
      if (Element::property($key) && self::isIgnoredProperty($key)) {
        unset($element[$key]);
      }
    }
    return $element;
  }

  /**
   * Determine if an element's property should be ignored.
   *
   * Subelement properties are delimited using __.
   *
   * @param string $property
   *   A property name.
   *
   * @return bool
   *   TRUE is the property should be ignored.
   *
   * @see \Drupal\webform\Element\WebformSelectOther
   * @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
   */
  protected static function isIgnoredProperty($property) {
    // Build cached ignored sub properties regular expression.
    if (!isset(self::$ignoredSubPropertiesRegExp)) {
      self::$ignoredSubPropertiesRegExp = '/__(' . implode('|', array_keys(WebformArrayHelper::removePrefix(self::$ignoredSubProperties))) . ')$/';
    }

    if (isset(self::$ignoredProperties[$property])) {
      return TRUE;
    }
    elseif (strpos($property, '__') !== FALSE && preg_match(self::$ignoredSubPropertiesRegExp, $property)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Merge element properties.
   *
   * @param array $elements
   *   An array of elements.
   * @param array $source_elements
   *   An array of elements to be merged.
   */
  public static function merge(array &$elements, array $source_elements) {
    foreach ($elements as $key => &$element) {
      if (!isset($source_elements[$key])) {
        continue;
      }

      $source_element = $source_elements[$key];
      if (gettype($element) !== gettype($source_element)) {
        continue;
      }

      if (is_array($element)) {
        self::merge($element, $source_element);
      }
      elseif (is_scalar($element)) {
        $elements[$key] = $source_element;
      }
    }
  }

  /**
   * Apply translation to element.
   *
   * IMPORTANT: This basically a modified version WebformElementHelper::merge()
   * that initially only merge element properties and ignores sub-element.
   *
   * @param array $element
   *   An element.
   * @param array $translation
   *   An associative array of translated element properties.
   */
  public static function applyTranslation(array &$element, array $translation) {
    foreach ($element as $key => &$value) {
      // Make sure to only merge properties.
      if (!Element::property($key) || empty($translation[$key])) {
        continue;
      }

      $translation_value = $translation[$key];
      if (gettype($value) !== gettype($translation_value)) {
        continue;
      }

      if (is_array($value)) {
        self::merge($value, $translation_value);
      }
      elseif (is_scalar($value)) {
        $element[$key] = $translation_value;
      }
    }
  }

  /**
   * Flatten a nested array of elements.
   *
   * @param array $elements
   *   An array of elements.
   *
   * @return array
   *   A flattened array of elements.
   */
  public static function getFlattened(array $elements) {
    $flattened_elements = [];
    foreach ($elements as $key => &$element) {
      if (Element::property($key) || !is_array($element)) {
        continue;
      }

      $flattened_elements[$key] = self::getProperties($element);
      $flattened_elements += self::getFlattened($element);
    }
    return $flattened_elements;
  }

  /**
   * Convert all render(able) markup into strings.
   *
   * This method is used to prevent objects from being serialized on form's
   * that are using #ajax callbacks or rebuilds.
   *
   * @param array $elements
   *   An associative array of elements.
   */
  public static function convertRenderMarkupToStrings(array &$elements) {
    foreach ($elements as $key => &$value) {
      if (is_array($value)) {
        self::convertRenderMarkupToStrings($value);
      }
      elseif ($value instanceof MarkupInterface) {
        $elements[$key] = (string) $value;
      }
    }
  }

  /**
   * Convert element or property to a string.
   *
   * This method is used to prevent 'Array to string conversion' errors.
   *
   * @param array|string|MarkupInterface $element
   *   An element, render array, string, or markup.
   *
   * @return string
   *   The element or property to a string.
   */
  public static function convertToString($element) {
    if (is_array($element)) {
      return (string) \Drupal::service('renderer')->renderPlain($element);
    }
    else {
      return (string) $element;
    }
  }

  /****************************************************************************/
  // Validate callbacks to trigger or suppress validation.
  /****************************************************************************/

  /****************************************************************************/
  // ISSUE: Hidden elements still need to call #element_validate because
  // certain elements, including managed_file, checkboxes, password_confirm,
  // etc..., will also massage the submitted values via #element_validate.
  //
  // SOLUTION: Call #element_validate for all hidden elements but suppresses
  // #element_validate errors.
  /****************************************************************************/

  /**
   * Set element validate callback.
   *
   * @param array $element
   *   An element.
   * @param array $element_validate
   *   Element validate callback.
   *
   * @return array
   *   The element with validate callback.
   *
   * @see \Drupal\webform\Plugin\WebformElementBase::hiddenElementAfterBuild
   * @see \Drupal\webform\WebformSubmissionConditionsValidator::elementAfterBuild
   */
  public static function setElementValidate(array $element, array $element_validate = [WebformElementHelper::class, 'suppressElementValidate']) {
    // Element validation can only overridden once so we need to reset
    // the #eleemnt_validate callback.
    if (isset($element['#_element_validate'])) {
      $element['#element_validate'] = $element['#_element_validate'];
      unset($element['#_element_validate']);
    }

    // Wrap #element_validate so that we suppress validation error messages.
    // This only applies visible elements (#access: TRUE) with
    // #element_validate callbacks which are also conditionally hidden.
    if (!empty($element['#element_validate'])) {
      $element['#_element_validate'] = $element['#element_validate'];
      $element['#element_validate'] = [$element_validate];
    }
    return $element;
  }

  /**
   * Webform element #element_validate callback: Execute #element_validate and suppress errors.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function triggerElementValidate(array &$element, FormStateInterface $form_state) {
    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    foreach ($element['#_element_validate'] as $callback) {
      $complete_form = &$form_state->getCompleteForm();
      call_user_func_array($form_state->prepareCallback($callback), [&$element, &$form_state, &$complete_form]);
    }
  }

  /**
   * Webform element #element_validate callback: Execute #element_validate and suppress errors.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function suppressElementValidate(array &$element, FormStateInterface $form_state) {
    // Create a temp webform state that will capture and suppress all element
    // validation errors.
    $temp_form_state = clone $form_state;
    $temp_form_state->setLimitValidationErrors([]);

    // @see \Drupal\Core\Form\FormValidator::doValidateForm
    foreach ($element['#_element_validate'] as $callback) {
      $complete_form = &$form_state->getCompleteForm();
      call_user_func_array($form_state->prepareCallback($callback), [&$element, &$temp_form_state, &$complete_form]);
    }

    // Get the temp webform state's values.
    $form_state->setValues($temp_form_state->getValues());
  }

  /**
   * Set form state required error for a specified element.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param string $title
   *   OPTIONAL. Required error title.
   */
  public static function setRequiredError(array $element, FormStateInterface $form_state, $title = NULL) {
    if (isset($element['#required_error'])) {
      $form_state->setError($element, $element['#required_error']);
    }
    elseif ($title) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $title]));
    }
    elseif (isset($element['#title'])) {
      $form_state->setError($element, t('@name field is required.', ['@name' => $element['#title']]));
    }
    else {
      $form_state->setError($element);
    }
  }

  /**
   * Get an element's #states.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An associative array containing an element's states.
   */
  public static function getStates(array $element) {
    // Composite and multiple elements use use a custom states wrapper
    // which will changes '#states' to '#_webform_states'.
    // @see \Drupal\webform\Utility\WebformElementHelper::fixStatesWrapper
    if (!empty($element['#_webform_states'])) {
      return $element['#_webform_states'];
    }
    elseif (!empty($element['#states'])) {
      return $element['#states'];
    }
    else {
      return [];
    }
  }

  /**
   * Get required #states from an element's visible #states.
   *
   * This method allows composite and multiple to conditionally
   * require sub-elements when they are visible.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An associative array containing 'visible' and 'invisible' selectors
   *   and triggers.
   */
  public static function getRequiredFromVisibleStates(array $element) {
    $states = WebformElementHelper::getStates($element);
    $required_states = [];
    if (!empty($states['visible'])) {
      $required_states['required'] = $states['visible'];
    }
    if (!empty($states['invisible'])) {
      $required_states['optional'] = $states['invisible'];
    }
    return $required_states;
  }

}
