<?php

namespace Drupal\webform\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\OptGroup;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Entity\WebformOptions;
use Drupal\webform\Plugin\WebformElement\Checkbox;
use Drupal\webform\Plugin\WebformElement\Checkboxes;
use Drupal\webform\Plugin\WebformElement\Details;
use Drupal\webform\Twig\TwigExtension;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformFormHelper;
use Drupal\webform\Utility\WebformHtmlHelper;
use Drupal\webform\Utility\WebformOptionsHelper;
use Drupal\webform\Utility\WebformReflectionHelper;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformSubmissionConditionsValidator;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\WebformTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for a webform element.
 *
 * @see \Drupal\webform\Plugin\WebformElementInterface
 * @see \Drupal\webform\Plugin\WebformElementManager
 * @see \Drupal\webform\Plugin\WebformElementManagerInterface
 * @see plugin_api
 */
class WebformElementBase extends PluginBase implements WebformElementInterface {

  use StringTranslationTrait;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The webform element manager.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * The libraries manager.
   *
   * @var \Drupal\webform\WebformLibrariesManagerInterface
   */
  protected $librariesManager;

  /**
   * The webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * Constructs a WebformElementBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   * @param \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager
   *   The webform libraries manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->logger = $logger;
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->elementInfo = $element_info;
    $this->elementManager = $element_manager;
    $this->tokenManager = $token_manager;
    $this->librariesManager = $libraries_manager;
    $this->submissionStorage = $entity_type_manager->getStorage('webform_submission');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('logger.factory')->get('webform'),
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.element_info'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform.token_manager'),
      $container->get('webform.libraries_manager')
    );
  }

  /****************************************************************************/
  // Property methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   *
   * Only a few elements don't inherit these default properties.
   *
   * @see \Drupal\webform\Plugin\WebformElement\Textarea
   * @see \Drupal\webform\Plugin\WebformElement\WebformLikert
   * @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase
   * @see \Drupal\webform\Plugin\WebformElement\ContainerBase
   */
  public function getDefaultProperties() {
    $properties = [
      // Element settings.
      'title' => '',
      'default_value' => '',
      // Description/Help.
      'help' => '',
      'description' => '',
      'more' => '',
      'more_title' => '',
      // Form display.
      'title_display' => '',
      'description_display' => '',
      'field_prefix' => '',
      'field_suffix' => '',
      'disabled' => FALSE,
      // Form validation.
      'required' => FALSE,
      'required_error' => '',
      'unique' => FALSE,
      'unique_user' => FALSE,
      'unique_entity' => FALSE,
      'unique_error' => '',
      // Attributes.
      'wrapper_attributes' => [],
      'attributes' => [],
      // Submission display.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_items' => $this->getItemsDefaultFormat(),
      'format_items_html' => '',
      'format_items_text' => '',
    ];

    $properties += $this->getDefaultBaseProperties();

    return $properties;
  }

  /**
   * Get default multiple properties used by most elements.
   *
   * @return array
   *   An associative array containing default multiple properties.
   */
  protected function getDefaultMultipleProperties() {
    return [
      'multiple' => FALSE,
      'multiple__header_label' => '',
      'multiple__label' => $this->t('item'),
      'multiple__labels' => $this->t('items'),
      'multiple__min_items' => 1,
      'multiple__empty_items' => 1,
      'multiple__add_more' => 1,
      'multiple__sorting' => TRUE,
      'multiple__operations' => TRUE,
    ];
  }

  /**
   * Get default base properties used by all elements.
   *
   * @return array
   *   An associative array containing base properties used by all elements.
   */
  protected function getDefaultBaseProperties() {
    return [
      // Administration.
      'admin_title' => '',
      'prepopulate' => FALSE,
      'private' => FALSE,
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      // Element access.
      'access_create_roles' => ['anonymous', 'authenticated'],
      'access_create_users' => [],
      'access_create_permissions' => [],
      'access_update_roles' => ['anonymous', 'authenticated'],
      'access_update_users' => [],
      'access_update_permissions' => [],
      'access_view_roles' => ['anonymous', 'authenticated'],
      'access_view_users' => [],
      'access_view_permissions' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableProperties() {
    return [
      'title',
      'label',
      'help',
      'more',
      'more_title',
      'description',
      'field_prefix',
      'field_suffix',
      'required_error',
      'unique_error',
      'admin_title',
      'placeholder',
      'markup',
      'test',
      'default_value',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function hasProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return isset($default_properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperty($property_name) {
    $default_properties = $this->getDefaultProperties();
    return (isset($default_properties[$property_name])) ? $default_properties[$property_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getElementProperty(array $element, $property_name) {
    return (isset($element["#$property_name"])) ? $element["#$property_name"] : $this->getDefaultProperty($property_name);
  }

  /**
   * Set element's default callback.
   *
   * This makes sure that an element's default callback is not clobbered by
   * any additional callbacks.
   *
   * @param array $element
   *   A render element.
   * @param string $callback_name
   *   A render element's callback.
   */
  protected function setElementDefaultCallback(array &$element, $callback_name) {
    $callback_name = ($callback_name[0] !== '#') ? '#' . $callback_name : $callback_name;
    $callback_value = $this->getElementInfoDefaultProperty($element, $callback_name) ?: [];
    if (!empty($element[$callback_name])) {
      $element[$callback_name] = array_merge($callback_value, $element[$callback_name]);
    }
    else {
      $element[$callback_name] = $callback_value;
    }
  }

  /**
   * Get a render element's default property.
   *
   * @param array $element
   *   A render element.
   * @param string $property_name
   *   An element's property name.
   *
   * @return mixed
   *   A render element's default value, or NULL if
   *   property does not exist.
   */
  protected function getElementInfoDefaultProperty(array $element, $property_name) {
    if (!isset($element['#type'])) {
      return NULL;
    }
    $property_name = ($property_name[0] !== '#') ? '#' . $property_name : $property_name;
    $type = $element['#type'];
    return $property_value = $this->elementInfo->getInfoProperty($type, $property_name, NULL)
      ?: $this->elementInfo->getInfoProperty("webform_$type", $property_name, NULL);
  }

  /****************************************************************************/
  // Definition and meta data methods.
  /****************************************************************************/

  /**
   * Get the Webform element's form element class definition.
   *
   * We use the plugin's base id here to support plugin derivatives.
   *
   * @return string
   *   A form element class definition.
   */
  protected function getFormElementClassDefinition() {
    $definition = $this->elementInfo->getDefinition($this->getBaseId());
    return $definition['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginApiUrl() {
    return (!empty($this->pluginDefinition['api'])) ? Url::fromUri($this->pluginDefinition['api']) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginApiLink() {
    $api_url = $this->getPluginApiUrl();
    return ($api_url) ? Link::fromTextAndUrl($this->getPluginLabel(), $api_url) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTypeName() {
    return $this->pluginDefinition['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultKey() {
    return (isset($this->pluginDefinition['default_key'])) ? $this->pluginDefinition['default_key'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return (!empty($element['#type'])) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasWrapper(array $element) {
    return $this->hasProperty('wrapper_attributes');
  }

  /**
   * {@inheritdoc}
   */
  public function isContainer(array $element) {
    return ($this->isInput($element)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isRoot() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return $this->hasProperty('multiple');
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleValues(array $element) {
    if ($this->hasProperty('multiple')) {
      if (isset($element['#multiple'])) {
        return $element['#multiple'];
      }
      else {
        $default_property = $this->getDefaultProperties();
        return $default_property['multiple'];
      }
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    $item_format = $this->getItemFormat($element);
    $items_format = $this->getItemsFormat($element);

    // Check is multi value element.
    if ($this->hasMultipleValues($element) && in_array($items_format, ['ol', 'ul', 'hr', 'custom'])) {
      return TRUE;
    }

    // Check if custom items template has HTML block tags.
    if ($items_format == 'custom' && isset($element['#format_items_html']) && WebformHtmlHelper::hasBlockTags($element['#format_items_html'])) {
      return TRUE;
    }

    // Check if custom item template has HTML block tags.
    if ($item_format == 'custom' && isset($element['#format_html']) && WebformHtmlHelper::hasBlockTags($element['#format_html'])) {
      return TRUE;
    }

    return $this->pluginDefinition['multiline'];
  }

  /**
   * {@inheritdoc}
   */
  public function isComposite() {
    return $this->pluginDefinition['composite'];
  }

  /**
   * {@inheritdoc}
   */
  public function isHidden() {
    return $this->pluginDefinition['hidden'];
  }

  /**
   * {@inheritdoc}
   */
  public function isExcluded() {
    return $this->configFactory->get('webform.settings')->get('element.excluded_elements.' . $this->pluginDefinition['id']) ? TRUE : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return !$this->isExcluded();
  }

  /**
   * {@inheritdoc}
   */
  public function isDisabled() {
    return !$this->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return $this->elementInfo->getInfo($this->getBaseId());
  }

  /****************************************************************************/
  // Element relationship methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    $types = [];

    $parent_classes = WebformReflectionHelper::getParentClasses($this, 'WebformElementBase');

    $plugin_id = $this->getPluginId();
    $is_container = $this->isContainer($element);
    $has_multiple_values = $this->hasMultipleValues($element);
    $is_multiline = $this->isMultiline($element);

    $elements = $this->elementManager->getInstances();
    foreach ($elements as $element_name => $element_instance) {
      // Skip self.
      if ($plugin_id == $element_instance->getPluginId()) {
        continue;
      }

      // Skip disable or hidden.
      if (!$element_instance->isEnabled() || $element_instance->isHidden()) {
        continue;
      }

      // Compare element base (abstract) class.
      $element_instance_parent_classes = WebformReflectionHelper::getParentClasses($element_instance, 'WebformElementBase');
      if ($parent_classes[1] != $element_instance_parent_classes[1]) {
        continue;
      }

      // Compare container, supports/has multiple values, and multiline.
      if ($is_container != $element_instance->isContainer($element)) {
        continue;
      }
      if ($has_multiple_values != $element_instance->hasMultipleValues($element)) {
        continue;
      }
      if ($is_multiline != $element_instance->isMultiline($element)) {
        continue;
      }

      $types[$element_name] = $element_instance->getPluginLabel();
    }

    asort($types);
    return $types;
  }

  /****************************************************************************/
  // Element rendering methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    // Set element options.
    if (isset($element['#options'])) {
      $element['#options'] = WebformOptions::getElementOptions($element);
    }

    // Set #admin_title to #title without any HTML markup.
    if (!empty($element['#title']) && empty($element['#admin_title'])) {
      $element['#admin_title'] = strip_tags($element['#title']);
    }

    // Replace global tokens which could include the [site:name].
    $this->replaceTokens($element);
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    $attributes_property = ($this->hasWrapper($element)) ? '#wrapper_attributes' : '#attributes';
    if ($webform_submission) {
      // Add webform and webform_submission IDs to every element.
      $element['#webform'] = $webform_submission->getWebform()->id();
      $element['#webform_submission'] = $webform_submission->id();

      // Check is the element is disabled and hide it.
      if ($this->isDisabled()) {
        if ($webform_submission->getWebform()->access('edit')) {
          $this->displayDisabledWarning($element);
        }
        $element['#access'] = FALSE;
      }

      // Apply element specific access rules.
      $operation = ($webform_submission->isCompleted()) ? 'update' : 'create';
      $element['#access'] = $this->checkAccessRules($operation, $element);
    }

    // Add #allowed_tags.
    $allowed_tags = $this->configFactory->get('webform.settings')->get('element.allowed_tags');
    switch ($allowed_tags) {
      case 'admin':
        $element['#allowed_tags'] = Xss::getAdminTagList();
        break;

      case 'html':
        $element['#allowed_tags'] = Xss::getHtmlTagList();
        break;

      default:
        $element['#allowed_tags'] = preg_split('/ +/', $allowed_tags);
        break;
    }

    // Add autocomplete attribute.
    if (isset($element['#autocomplete'])) {
      $element['#attributes']['autocomplete'] = $element['#autocomplete'];
    }

    // Add inline title display support.
    // Inline fieldset layout is handled via webform_preprocess_fieldset().
    // @see webform_preprocess_fieldset()
    if (isset($element['#title_display']) && $element['#title_display'] == 'inline') {
      // Store reference to unset #title_display.
      $element['#_title_display'] = $element['#title_display'];
      unset($element['#title_display']);
      $element['#wrapper_attributes']['class'][] = 'webform-element--title-inline';
    }

    // Check description markup.
    if (isset($element['#description'])) {
      $element['#description'] = WebformHtmlEditor::checkMarkup($element['#description']);
    }

    // Add default description display.
    $default_description_display = $this->configFactory->get('webform.settings')->get('element.default_description_display');
    if ($default_description_display && !isset($element['#description_display']) && $this->hasProperty('description_display')) {
      $element['#description_display'] = $default_description_display;
    }

    // Add tooltip description display support.
    if (isset($element['#description_display']) && $element['#description_display'] === 'tooltip') {
      $element['#description_display'] = 'invisible';
      $element[$attributes_property]['class'][] = 'js-webform-tooltip-element';
      $element[$attributes_property]['class'][] = 'webform-tooltip-element';
      $element['#attached']['library'][] = 'webform/webform.tooltip';
      // More is not supported with tooltip.
      unset($element['#more']);
    }

    // Add iCheck support.
    if ($this->hasProperty('icheck') && $this->librariesManager->isIncluded('jquery.icheck')) {
      $icheck = NULL;
      $icheck_skin = NULL;
      if (isset($element['#icheck'])) {
        if ($element['#icheck'] != 'none') {
          $icheck = $element['#icheck'];
          $icheck_skin = strtok($element['#icheck'], '-');
        }
      }
      elseif ($default_icheck = $this->configFactory->get('webform.settings')->get('element.default_icheck')) {
        $icheck = $default_icheck;
        $icheck_skin = strtok($default_icheck, '-');
      }
      if ($icheck) {
        $element['#attributes']['data-webform-icheck'] = $icheck;
        $element['#attached']['library'][] = 'webform/webform.element.icheck';
        $element['#attached']['library'][] = 'webform/libraries.jquery.icheck.' . $icheck_skin;
      }
    }

    // Add .webform-has-field-prefix and .webform-has-field-suffix class.
    if (!empty($element['#field_prefix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-prefix';
    }
    if (!empty($element['#field_suffix'])) {
      $element[$attributes_property]['class'][] = 'webform-has-field-suffix';
    }

    // Set element's #element_validate callback so that is not replaced by
    // additional #element_validate callbacks.
    $this->setElementDefaultCallback($element, 'element_validate');

    if ($this->isInput($element)) {
      // Handle #readonly support.
      // @see \Drupal\Core\Form\FormBuilder::handleInputElement
      if (!empty($element['#readonly'])) {
        $element['#attributes']['readonly'] = 'readonly';
        if ($this->hasProperty('wrapper_attributes')) {
          $element['#wrapper_attributes']['class'][] = 'webform-readonly';
        }
      }

      // Set custom required error message as 'data-required-error' attribute.
      // @see Drupal.behaviors.webformRequiredError
      // @see webform.form.js
      if (!empty($element['#required_error'])) {
        $element['#attributes']['data-webform-required-error'] = $element['#required_error'];
      }

      // Add webform element #minlength, #unique, and/or #multiple
      // validation handler.
      if (isset($element['#minlength'])) {
        $element['#element_validate'][] = [get_class($this), 'validateMinlength'];
      }
      if (isset($element['#unique'])) {
        $element['#element_validate'][] = [get_class($this), 'validateUnique'];
      }
      if (isset($element['#multiple']) && $element['#multiple'] > 1) {
        $element['#element_validate'][] = [get_class($this), 'validateMultiple'];
      }
    }

    // Replace tokens for all properties.
    if ($webform_submission) {
      $this->replaceTokens($element, $webform_submission);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finalize(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Prepare multiple element.
    $this->prepareMultipleWrapper($element);

    // Prepare #states and flexbox wrapper.
    $this->prepareWrapper($element);

    // Set hidden element #after_build handler.
    $element['#after_build'][] = [get_class($this), 'hiddenElementAfterBuild'];
  }

  /**
   * Webform element #after_build callback.
   *
   * Wrap #element_validate so that we suppress element validation errors.
   */
  public static function hiddenElementAfterBuild(array $element, FormStateInterface $form_state) {
    if (!isset($element['#access']) || $element['#access']) {
      return $element;
    }

    // Disabled #required validation for hidden elements.
    $element['#required'] = FALSE;

    return WebformElementHelper::setElementValidate($element);
  }

  /**
   * {@inheritdoc}
   */
  public function checkAccessRules($operation, array $element, AccountInterface $account = NULL) {
    // Respect elements that already have their #access set to FALSE.
    if (isset($element['#access']) && $element['#access'] === FALSE) {
      return FALSE;
    }

    $account = $account ?: $this->currentUser;
    return $this->checkAccessRule($element, $operation, $account);
  }

  /**
   * Checks an access rule against a user account's roles and id.
   *
   * @param array $element
   *   The element.
   * @param string $operation
   *   The operation.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user session for which to check access.
   *
   * @return bool
   *   The access result. Returns a TRUE if access is allowed.
   *
   * @see \Drupal\webform\Entity\Webform::checkAccessRule
   */
  protected function checkAccessRule(array $element, $operation, AccountInterface $account) {
    // If no access rules are set return TRUE.
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultBaseProperties
    if (!isset($element['#access_' . $operation . '_roles'])
      && !isset($element['#access_' . $operation . '_users'])
      && !isset($element['#access_' . $operation . '_permissions'])) {
      return TRUE;
    }

    // If access roles are not set then use the anonymous and authenticated
    // roles from the element's default properties.
    // @see \Drupal\webform\Plugin\WebformElementBase::getDefaultBaseProperties
    if (!isset($element['#access_' . $operation . '_roles'])) {
      $element['#access_' . $operation . '_roles'] = $this->getDefaultProperty('access_' . $operation . '_roles') ?: [];
    }
    if (array_intersect($element['#access_' . $operation . '_roles'], $account->getRoles())) {
      return TRUE;
    }

    if (isset($element['#access_' . $operation . '_users']) && in_array($account->id(), $element['#access_' . $operation . '_users'])) {
      return TRUE;
    }

    if (isset($element['#access_' . $operation . '_permissions'])) {
      foreach ($element['#access_' . $operation . '_permissions'] as $permission) {
        if ($account->hasPermission($permission)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function replaceTokens(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    foreach ($element as $key => $value) {
      if (!Element::child($key)) {
        $element[$key] = $this->tokenManager->replace($value, $webform_submission);
      }
    }
  }

  /**
   * Set an elements #states and flexbox wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareWrapper(array &$element) {
    $has_states_wrapper = $this->pluginDefinition['states_wrapper'];
    $has_flexbox_wrapper = !empty($element['#webform_parent_flexbox']);
    if (!$has_states_wrapper && !$has_flexbox_wrapper) {
      return;
    }

    $class = get_class($this);

    // Set element's #pre_render callback so that is not replaced by
    // additional element #pre_render callbacks.
    $this->setElementDefaultCallback($element, 'pre_render');

    // Fix #states wrapper.
    if ($has_states_wrapper) {
      $element['#pre_render'][] = [$class, 'preRenderFixStatesWrapper'];
    }

    // Add flex(box) wrapper.
    if ($has_flexbox_wrapper) {
      $element['#pre_render'][] = [$class, 'preRenderFixFlexboxWrapper'];
    }
  }

  /**
   * Fix state wrapper.
   *
   * Notes:
   * - Certain elements don't support #states so a workaround is to adds a
   *   wrapper that renders the #states in a #prefix and #suffix div tag.
   * - Composite elements tend not to properly handle #states.
   * - Composite elements need propagate a visible/hidden #states to
   *   sub-element required #state.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An element with #states added to the #prefix and #suffix.
   */
  public static function preRenderFixStatesWrapper(array $element) {
    WebformElementHelper::fixStatesWrapper($element);
    return $element;
  }

  /**
   * Fix flexbox wrapper.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An element with flexbox wrapper added to the #prefix and #suffix.
   */
  public static function preRenderFixFlexboxWrapper(array $element) {
    $flex = (isset($element['#flex'])) ? $element['#flex'] : 1;
    $element += ['#prefix' => '', '#suffix' => ''];
    $element['#prefix'] = '<div class="webform-flex webform-flex--' . $flex . '"><div class="webform-flex--container">' . $element['#prefix'];
    $element['#suffix'] = $element['#suffix'] . '</div></div>';
    return $element;
  }

  /**
   * Set multiple element wrapper.
   *
   * @param array $element
   *   An element.
   */
  protected function prepareMultipleWrapper(array &$element) {
    if (!$this->hasMultipleValues($element) || !$this->hasMultipleWrapper() || empty($element['#multiple'])) {
      return;
    }

    // Set the multiple element.
    $element['#element'] = $element;
    // Remove properties that should only be applied to the parent element.
    $element['#element'] = array_diff_key($element['#element'], array_flip(['#default_value', '#description', '#description_display', '#required', '#required_error', '#states', '#wrapper_attributes', '#prefix', '#suffix', '#element', '#tags', '#multiple']));
    // Propagate #states to sub element.
    // @see \Drupal\webform\Element\WebformCompositeBase::processWebformComposite
    if (!empty($element['#states'])) {
      $element['#element']['#_webform_states'] = $element['#states'];
    }
    // Always make the title invisible.
    $element['#element']['#title_display'] = 'invisible';

    // Change the element to a multiple element.
    $element['#type'] = 'webform_multiple';
    $element['#webform_multiple'] = TRUE;

    // Set cardinality from #multiple.
    if ($element['#multiple'] > 1) {
      $element['#cardinality'] = $element['#multiple'];
    }

    // Apply multiple properties.
    $multiple_properties = $this->getDefaultMultipleProperties();
    foreach ($multiple_properties as $multiple_property => $multiple_value) {
      if (strpos($multiple_property, 'multiple__') === 0) {
        $property_name = str_replace('multiple__', '', $multiple_property);
        $element["#$property_name"] = (isset($element["#$multiple_property"])) ? $element["#$multiple_property"] : $multiple_value;
      }
    }

    // If header label is defined use it for the #header.
    if (!empty($element['#multiple__header_label'])) {
      $element['#header'] = $element['#multiple__header_label'];
    }

    // Remove properties that should only be applied to the child element.
    $element = array_diff_key($element, array_flip(['#attributes', '#field_prefix', '#field_suffix', '#pattern', '#placeholder', '#maxlength', '#element_validate', '#pre_render']));

    // Apply #unique multiple validation.
    if (isset($element['#unique'])) {
      $element['#element_validate'][] = [get_class($this), 'validateUniqueMultiple'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    $t_args = [
      '%title' => $this->getLabel($element),
      '%type' => $this->getPluginLabel(),
      ':href' => Url::fromRoute('webform.config.elements')->toString(),
    ];
    if ($this->currentUser->hasPermission('administer webform')) {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Go to the <a href=":href">admin settings</a> page to enable this element.', $t_args);
    }
    else {
      $message = $this->t('%title is a %type element, which has been disabled and will not be rendered. Please contact a site administrator.', $t_args);
    }
    drupal_set_message($message, 'warning');

    $context = [
      '@title' => $this->getLabel($element),
      '@type' => $this->getPluginLabel(),
      'link' => Link::fromTextAndUrl($this->t('Edit'), Url::fromRoute('<current>'))->toString(),
    ];
    $this->logger->notice("'@title' is a '@type' element, which has been disabled and will not be rendered.", $context);
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {}

  /**
   * {@inheritdoc}
   */
  public function getLabel(array $element) {
    return $element['#title'] ?: $element['#webform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdminLabel(array $element) {
    return $element['#admin_title'] ?: $element['#title'] ?: $element['#webform_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function getKey(array $element) {
    return $element['#webform_key'];
  }

  /****************************************************************************/
  // Display submission value methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function buildHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->build('html', $element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->build('text', $element, $webform_submission, $options);
  }

  /**
   * Build an element as text or HTML.
   *
   * @param string $format
   *   Format of the element, text or html.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   A render array representing an element as text or HTML.
   */
  protected function build($format, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $options += [
      'exclude_empty' => TRUE,
    ];
    $options['multiline'] = $this->isMultiline($element);
    $format_function = 'format' . ucfirst($format);
    $value = $this->$format_function($element, $webform_submission, $options);

    // Handle empty value.
    if ($value === '') {
      // Return NULL for empty formatted value.
      if (!empty($options['exclude_empty'])) {
        return NULL;
      }
      // Else set the formatted value to empty message/placeholder.
      else {
        $value = $this->configFactory->get('webform.settings')->get('element.empty_message');
      }
    }

    // Convert string to renderable #markup.
    if (is_string($value)) {
      $value = ['#' . ($format === 'text' ? 'plain_text' : 'markup') => $value];
    }

    return [
      '#theme' => 'webform_element_base_' . $format,
      '#element' => $element,
      '#value' => $value,
      '#webform_submission' => $webform_submission,
      '#options' => $options,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtml(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->format('Html', $element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function formatText(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->format('Text', $element, $webform_submission, $options);
  }

  /**
   * Format an element's value as HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as plain text or a render array.
   */
  protected function format($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!$this->hasValue($element, $webform_submission, $options) && !$this->isContainer($element)) {
      return '';
    }

    $item_function = 'format' . $type . 'Item';
    $items_function = 'format' . $type . 'Items';
    if ($this->hasMultipleValues($element)) {
      // Return $options['delta'] which is used by tokens.
      // @see _webform_token_get_submission_value()
      if (isset($options['delta'])) {
        return $this->$item_function($element, $webform_submission, $options);
      }
      elseif ($this->getItemsFormat($element) == 'custom') {
        return $this->formatCustomItems($type, $element, $webform_submission, $options);
      }
      else {
        return $this->$items_function($element, $webform_submission, $options);
      }
    }
    else {
      if ($this->getItemFormat($element) == 'custom') {
        return $this->formatCustomItem($type, $element, $webform_submission, $options);
      }
      else {
        return $this->$item_function($element, $webform_submission, $options);
      }
    }
  }

  /**
   * Format an element's items using custom HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's items formatted as plain text or a render array.
   */
  protected function formatCustomItems($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $name = strtolower($type);

    // Get value.
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    $item_function = 'format' . $type . 'Item';
    foreach (array_keys($value) as $delta) {
      $items[] = $this->$item_function($element, $webform_submission, ['delta' => $delta] + $options);
    }

    // Get template.
    $template = trim($element['#format_items_' . $name]);

    // Get context.
    $context = (isset($options['context'])) ? $options['context'] : [];
    $context += [
      'value' => $value,
      'items' => $items,
      'data' => $webform_submission->getData(),
    ];

    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => $context,
    ];
  }

  /**
   * Format an element's items as HTML.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string|array
   *   The element's items as HTML.
   */
  protected function formatHtmlItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    foreach (array_keys($value) as $delta) {
      $items[] = $this->formatHtmlItem($element, $webform_submission, ['delta' => $delta] + $options);
    }

    $format = $this->getItemsFormat($element);
    switch ($format) {
      case 'ol':
      case 'ul':
        return [
          '#theme' => 'item_list',
          '#items' => $items,
          '#list_type' => $format,
        ];

      case 'and':
        $total = count($items);
        if ($total === 1) {
          $item = current($items);
          return is_array($item) ? $item : ['#markup' => $item];
        }

        $build = [];
        foreach ($items as $index => &$item) {
          $build[] = (is_array($item)) ? $item : ['#markup' => $item];
          if ($total === 2 && $index === 0) {
            $build[] = ['#markup' => ' ' . t('and') . ' '];
          }
          elseif ($index !== ($total - 1)) {
            if ($index === ($total - 2)) {
              $build[] = ['#markup' => ', ' . t('and') . ' '];
            }
            else {
              $build[] = ['#markup' => ', '];
            }
          }
        }
        return $build;

      default:
      case 'br':
      case 'semicolon':
      case 'comma':
      case 'space':
      case 'hr':
        $delimiters = [
          'hr' => '<hr class="webform-horizontal-rule" />',
          'br' => '<br />',
          'semicolon' => '; ',
          'comma' => ', ',
          'space' => ' ',
        ];
        $delimiter = (isset($delimiters[$format])) ? $delimiters[$format] : $format;

        $total = count($items);

        $build = [];
        foreach ($items as $index => &$item) {
          $build[] = (is_array($item)) ? $item : ['#markup' => $item];
          if ($index !== ($total - 1)) {
            $build[] = ['#markup' => $delimiter];
          }
        }
        return $build;
    }
  }

  /**
   * Format an element's items as text.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's items as text.
   */
  protected function formatTextItems(array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);

    // Get items.
    $items = [];
    foreach (array_keys($value) as $delta) {
      $items[] = $this->formatTextItem($element, $webform_submission, ['delta' => $delta] + $options);
    }

    $format = $this->getItemsFormat($element);
    switch ($format) {
      case 'ol':
        $list = [];
        $index = 1;
        foreach ($items as $item) {
          $prefix = ($index++) . '. ';
          $list[] = $prefix . str_replace(PHP_EOL, PHP_EOL . str_repeat(' ', strlen($prefix)), $item);
        }
        return implode(PHP_EOL, $list);

      case 'ul':
        $list = [];
        foreach ($items as $index => $item) {
          $list[] = '- ' . str_replace(PHP_EOL, PHP_EOL . '  ', $item);
        }
        return implode(PHP_EOL, $list);

      case 'and':
        return WebformArrayHelper::toString($items);

      default:
      case 'br':
      case 'semicolon':
      case 'comma':
      case 'space':
      case 'hr':
        $delimiters = [
          'hr' => PHP_EOL . '---' . PHP_EOL,
          'br' => PHP_EOL,
          'semicolon' => '; ',
          'comma' => ', ',
          'space' => ' ',
        ];
        $delimiter = (isset($delimiters[$format])) ? $delimiters[$format] : $format;
        return implode($delimiter, $items);
    }
  }

  /**
   * Format an element's item using custom HTML or plain text.
   *
   * @param string $type
   *   The format type, HTML or Text.
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's item formatted as plain text or a render array.
   */
  protected function formatCustomItem($type, array &$element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $name = strtolower($type);

    // Get template.
    $template = trim($element['#format_' . $name]);

    // Get context.
    $context = (isset($options['context'])) ? $options['context'] : [];

    // Get context.value.
    $value = $this->getValue($element, $webform_submission, $options);
    $context['value'] = $value;

    // Get content.item.
    $context['item'] = [];
    // Parse item.format from template and add to context.
    if (preg_match_all("/item(?:\[['\"]|\.)([a-zA-Z0-9-_:]+)/", $template, $matches)) {
      $formats = array_unique($matches[1]);
      $item_function = 'format' . $type . 'Item';
      foreach ($formats as $format) {
        $context['item'][$format] = $this->$item_function(['#format' => $format] + $element, $webform_submission, $options);
      }
    }

    // Add submission data to context.
    $context['data'] = $webform_submission->getData();

    // Return inline template.
    return [
      '#type' => 'inline_template',
      '#template' => $template,
      '#context' => $context,
    ];
  }

  /**
   * Format an element's value as HTML.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return array|string
   *   The element's value formatted as HTML or a render array.
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $format = $this->getItemFormat($element);
    $value = $this->formatTextItem($element, $webform_submission, ['prefixing' => FALSE] + $options);

    if ($format === 'raw') {
      return Markup::create($value);
    }

    // Build a render that used #plain_text so that HTML characters are escaped.
    // @see \Drupal\Core\Render\Renderer::ensureMarkupIsSafe
    if ($value === '0') {
      // Issue #2765609: #plain_text doesn't render empty-like values
      // (e.g. 0 and "0").
      // Workaround: Use #markup until this issue is fixed.
      $build = ['#markup' => $value];
    }
    else {
      $build = ['#plain_text' => $value];
    }

    $options += ['prefixing' => TRUE];
    if ($options['prefixing']) {
      if (isset($element['#field_prefix'])) {
        $build['#prefix'] = $element['#field_prefix'];
      }
      if (isset($element['#field_suffix'])) {
        $build['#suffix'] = $element['#field_suffix'];
      }
    }

    return $build;
  }

  /**
   * Format an element's value as text.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   An array of options.
   *
   * @return string
   *   The element's value formatted as text.
   *
   * @see _webform_token_get_submission_value()
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $format = $this->getItemFormat($element);

    if ($format === 'raw') {
      return $value;
    }

    $options += ['prefixing' => TRUE];
    if ($options['prefixing']) {
      if (isset($element['#field_prefix'])) {
        $value = strip_tags($element['#field_prefix']) . $value;
      }
      if (isset($element['#field_suffix'])) {
        $value .= strip_tags($element['#field_suffix']);
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    return ($value === '' || $value === NULL || (is_array($value) && empty($value))) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    if (!isset($element['#webform_key']) && isset($element['#value'])) {
      return $element['#value'];
    }

    $webform_key = (isset($options['webform_key'])) ? $options['webform_key'] : $element['#webform_key'];
    $value = $webform_submission->getElementData($webform_key);
    // Is value is NULL and there is a #default_value, then use it.
    if ($value === NULL && isset($element['#default_value'])) {
      $value = $element['#default_value'];
    }

    // Return multiple (delta) value or composite (composite_key) value.
    if (is_array($value)) {
      // Return $options['delta'] which is used by tokens.
      // @see _webform_token_get_submission_value()
      if (isset($options['delta'])) {
        $value = (isset($value[$options['delta']])) ? $value[$options['delta']] : NULL;
      }

      // Return $options['composite_key'] which is used by composite elements.
      // @see \Drupal\webform\Plugin\WebformElement\WebformCompositeBase::formatTableColumn
      if ($value && isset($options['composite_key'])) {
        $value = (isset($value[$options['composite_key']])) ? $value[$options['composite_key']] : NULL;
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRawValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $element['#format'] = 'raw';
    return $this->getValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return [
      'value' => $this->t('Value'),
      'raw' => $this->t('Raw value'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormat(array $element) {
    if (isset($element['#format'])) {
      return $element['#format'];
    }
    elseif ($default_format = $this->configFactory->get('webform.settings')->get('format.' . $this->getPluginId() . '.item')) {
      return $default_format;
    }
    else {
      return $this->getItemDefaultFormat();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'ul';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormats() {
    return [
      'comma' => $this->t('Comma'),
      'semicolon' => $this->t('Semicolon'),
      'and' => $this->t('And'),
      'ol' => $this->t('Ordered list'),
      'ul' => $this->t('Unordered list'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormat(array $element) {
    if (isset($element['#format_items'])) {
      return $element['#format_items'];
    }
    elseif ($default_format = $this->configFactory->get('webform.settings')->get('format.' . $this->getPluginId() . '.items')) {
      return $default_format;
    }
    else {
      return $this->getItemsDefaultFormat();
    }
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
    ];
  }

  /****************************************************************************/
  // Test methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return FALSE;
  }

  /****************************************************************************/
  // Table methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#webform_key'];
    return [
      'element__' . $key => [
        'title' => $this->getAdminLabel($element),
        'sort' => TRUE,
        'key' => $key,
        'property_name' => NULL,
        'element' => $element,
        'plugin' => $this,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->formatHtml($element, $webform_submission);
  }

  /****************************************************************************/
  // Export methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {}

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $export_options) {
    if ($export_options['header_format'] == 'label') {
      return [$this->getAdminLabel($element)];
    }
    else {
      return [$element['#webform_key']];
    }
  }

  /**
   * Prefix an element's export header.
   *
   * @param array $header
   *   An element's export header.
   * @param array $element
   *   An element.
   * @param array $export_options
   *   An associative array of export options.
   *
   * @return array
   *   An element's export header with prefix.
   */
  protected function prefixExportHeader(array $header, array $element, array $export_options) {
    if (empty($export_options['header_prefix'])) {
      return $header;
    }

    if ($export_options['header_format'] == 'label') {
      $prefix = $this->getAdminLabel($element) . $export_options['header_prefix_label_delimiter'];
    }
    else {
      $prefix = $this->getKey($element) . $export_options['header_prefix_key_delimiter'];
    }

    foreach ($header as $index => $column) {
      $header[$index] = $prefix . $column;
    }

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, WebformSubmissionInterface $webform_submission, array $export_options) {
    $element['#format_items'] = $export_options['multiple_delimiter'];
    return [$this->formatText($element, $webform_submission, $export_options)];
  }

  /****************************************************************************/
  // Validation methods.
  /****************************************************************************/

  /**
   * Form API callback. Validate element #minlength value.
   */
  public static function validateMinlength(&$element, FormStateInterface &$form_state) {
    if (!isset($element['#minlength'])) {
      return;
    }

    if (Unicode::strlen($element['#value']) < $element['#minlength']) {
      $t_args = [
        '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
        '%min' => $element['#minlength'],
        '%length' => Unicode::strlen($element['#value']),
      ];
      $form_state->setError($element, t('%name cannot be less than %min characters but is currently %length characters long.', $t_args));
    }
  }

  /**
   * Form API callback. Validate element #unique value.
   */
  public static function validateUnique(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#unique'])) {
      return;
    }

    $name = $element['#webform_key'];
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Skip empty unique fields or composite arrays.
    if ($value === '' || is_array($value)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = $form_object->getEntity();
    $webform = $webform_submission->getWebform();

    // Build unique query.
    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->leftJoin('webform_submission_data', 'wsd', 'ws.sid = wsd.sid');
    $query->fields('ws', ['sid']);
    $query->condition('wsd.webform_id', $webform->id());
    $query->condition('wsd.name', $name);
    $query->condition('wsd.value', $value);
    // Unique user condition.
    if (!empty($element['#unique_user'])) {
      $query->condition('ws.uid', $webform_submission->getOwnerId());
    }
    // Unique (source) entity condition.
    if (!empty($element['#unique_entity'])) {
      if ($source_entity = $webform_submission->getSourceEntity()) {
        $query->condition('ws.entity_type', $source_entity->getEntityTypeId());
        $query->condition('ws.entity_id', $source_entity->id());
      }
      else {
        $query->isNull('ws.entity_type');
        $query->isNull('ws.entity_id');
      }
    }
    // Exclude the current webform submission.
    if ($sid = $webform_submission->id()) {
      $query->condition('ws.sid', $sid, '<>');
    }
    // Using range() is more efficient than using countQuery() for data checks.
    $query->range(0, 1);
    $count = $query->execute()->fetchField();

    if ($count) {
      if (isset($element['#unique_error'])) {
        $form_state->setError($element, $element['#unique_error']);
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '%value' => $value,
        ];
        $form_state->setError($element, t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.', $t_args));
      }
      else {
        $form_state->setError($element);
      }
    }
  }

  /**
   * Form API callback. Validate element #unique multiple values.
   */
  public static function validateUniqueMultiple(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#unique'])) {
      return;
    }

    $name = $element['#name'];
    $value = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    if (empty($value)) {
      return;
    }

    // Compare number of values to unique number of values.
    if (count($value) != count(array_unique($value))) {
      $duplicates = WebformArrayHelper::getDuplicates($value);

      if (isset($element['#unique_error'])) {
        $form_state->setError($element, $element['#unique_error']);
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $name : $element['#title'],
          '%value' => reset($duplicates),
        ];
        $form_state->setError($element, t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.', $t_args));
      }
      else {
        $form_state->setError($element);
      }
    }
  }

  /**
   * Form API callback. Validate element #multiple > 1 value.
   */
  public static function validateMultiple(array &$element, FormStateInterface $form_state) {
    if (!isset($element['#multiple'])) {
      return;
    }

    // IMPORTANT: Must get values from the $form_states since sub-elements
    // may call $form_state->setValueForElement() via their validation hook.
    // @see \Drupal\webform\Element\WebformEmailConfirm::validateWebformEmailConfirm
    // @see \Drupal\webform\Element\WebformOtherBase::validateWebformOther
    $values = NestedArray::getValue($form_state->getValues(), $element['#parents']);

    // Skip empty values or values that are not an array.
    if (empty($values) || !is_array($values)) {
      return;
    }

    if (count($values) > $element['#multiple']) {
      if (isset($element['#multiple_error'])) {
        $form_state->setError($element, $element['#multiple_error']);
      }
      elseif (isset($element['#title'])) {
        $t_args = [
          '%name' => empty($element['#title']) ? $element['#parents'][0] : $element['#title'],
          '@count' => $element['#multiple'],
        ];
        $form_state->setError($element, t('%name: this element cannot hold more than @count values.', $t_args));
      }
      else {
        $form_state->setError($element);
      }
    }
  }

  /****************************************************************************/
  // #states API methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function getElementStateOptions() {
    $visibility_optgroup = (string) $this->t('Visibility');
    $state_optgroup = (string) $this->t('State');
    $validation_optgroup = (string) $this->t('Validation');
    $value_optgroup = (string) $this->t('Value');

    $states = [];

    // Set default states that apply to the element/container and sub elements.
    $states += [
      $visibility_optgroup => [
        'visible' => $this->t('Visible'),
        'invisible' => $this->t('Hidden'),
        'visible-slide' => $this->t('Visible (Slide)'),
        'invisible-slide' => $this->t('Hidden (Slide)'),
      ],
      $state_optgroup => [
        'enabled' => $this->t('Enabled'),
        'disabled' => $this->t('Disabled'),
      ],
      $validation_optgroup => [
        'required' => $this->t('Required'),
        'optional' => $this->t('Optional'),
      ]
    ];

    // Set readwrite/readonly states for any element that supports it
    // and containers.
    if ($this->hasProperty('readonly') || $this->isContainer(['#type' => $this->getPluginId()])) {
      $states[$state_optgroup] += [
        'readwrite' => $this->t('Read/write'),
        'readonly' => $this->t('Read-only'),
      ];
    }

    // Set checked/unchecked states for any element that contains checkboxes.
    if ($this instanceof Checkbox || $this instanceof Checkboxes) {
      $states[$value_optgroup] = [
        'checked' => $this->t('Checked'),
        'unchecked' => $this->t('Unchecked'),
      ];
    }

    // Set expanded/collapsed states for any details element.
    if ($this instanceof Details) {
      $states[$state_optgroup] += [
        'expanded' => $this->t('Expanded'),
        'collapsed' => $this->t('Collapsed'),
      ];
    }

    return $states;
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\webform\Entity\Webform::getElementsSelectorOptions
   */
  public function getElementSelectorOptions(array $element) {
    if ($this->hasMultipleValues($element) && $this->hasMultipleWrapper()) {
      return [];
    }

    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];

    if ($inputs = $this->getElementSelectorInputsOptions($element)) {
      $selectors = [];
      foreach ($inputs as $input_name => $input_title) {
        $selectors[":input[name=\"{$name}[{$input_name}]\"]"] = $input_title;
      }
      return [$title => $selectors];
    }
    else {
      return [":input[name=\"$name\"]" => $title];
    }
  }

  /**
   * Get an element's (sub)inputs selectors as options.
   *
   * @param array $element
   *   An element.
   *
   * @return array
   *   An array of element (sub)input selectors.
   */
  protected function getElementSelectorInputsOptions(array $element) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorInputValue($selector, $trigger, array $element, WebformSubmissionInterface $webform_submission) {
    if ($this->isComposite()) {
      $input_name = WebformSubmissionConditionsValidator::getSelectorInputName($selector);
      $composite_key = WebformSubmissionConditionsValidator::getInputNameAsArray($input_name, 1);
      if ($composite_key) {
        return $this->getRawValue($element, $webform_submission, ['composite_key' => $composite_key]);
      }
      else {
        return NULL;
      }
    }
    else {
      return $this->getRawValue($element, $webform_submission);
    }
  }

  /****************************************************************************/
  // Operation methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function preCreate(array &$element, array $values) {}

  /**
   * {@inheritdoc}
   */
  public function postCreate(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postLoad(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preDelete(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function preSave(array &$element, WebformSubmissionInterface $webform_submission) {}

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {}

  /****************************************************************************/
  // Element configuration methods.
  /****************************************************************************/

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\webform_ui\Form\WebformUiElementFormInterface $form_object */
    $form_object = $form_state->getFormObject();
    $webform = $form_object->getWebform();

    $element_properties = $form_state->get('element_properties');

    /**************************************************************************/
    // General.
    /**************************************************************************/

    /* Element settings */

    $form['element'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Element settings'),
      '#access' => TRUE,
      '#weight' => -50,
    ];
    $form['element']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#maxlength' => NULL,
      '#description' => $this->t('This is used as a descriptive label when displaying this webform element.'),
      '#required' => TRUE,
      '#attributes' => ['autofocus' => 'autofocus'],
    ];
    $form['element']['value'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Value'),
      '#description' => $this->t('The value of the webform element.'),
    ];
    $form['element']['multiple'] = [
      '#title' => $this->t('Allowed number of values'),
      '#type' => 'webform_element_multiple',
    ];
    $form['element']['multiple_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Custom allowed number of values error message'),
      '#description' => $this->t('If set, this message will be used when an element\'s allowed number of values is exceeded, instead of the default "@message" message.', ['@message' => $this->t('%name: this element cannot hold more than @count values.')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['value' => 'number'],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1],
        ],
      ],
    ];

    /* Element description */

    $form['element_description'] = [
      '#type' => 'details',
      '#title' => $this->t('Element description/help'),
    ];
    $form['element_description']['help'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Help text'),
      '#description' => $this->t('A tooltip displayed after the title.'),
      '#states' => [
        'invisible' => [
          [':input[name="properties[title_display]"]' => ['value' => 'invisible']],
          'or',
          [':input[name="properties[title_display]"]' => ['value' => 'attribute']],
        ],
      ],
    ];
    $form['element_description']['description'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('Description'),
      '#description' => $this->t('A short description of the element used as help for the user when he/she uses the webform.'),
    ];
    $form['element_description']['more_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('More label'),
      '#description' => $this->t('The click-able label used to open and close more text.') . '<br /><br />' .
        $this->t('Defaults to: %value', ['%value' => $this->configFactory->get('webform.settings')->get('element.default_more_title')]),
      '#states' => [
        'invisible' => [
          ':input[name="properties[description_display]"]' => ['value' => 'tooltip'],
        ],
      ],
    ];
    $form['element_description']['more'] = [
      '#type' => 'webform_html_editor',
      '#title' => $this->t('More text'),
      '#description' => $this->t('A long description of the element that provides form additional information which can opened and closed.'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[description_display]"]' => ['value' => 'tooltip'],
        ],
      ],
    ];

    /* Form display */

    $form['form'] = [
      '#type' => 'details',
      '#title' => $this->t('Form display'),
    ];
    $form['form']['display_container'] = $this->getFormInlineContainer();
    $form['form']['display_container']['title_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Title display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'inline' => $this->t('Inline'),
        'invisible' => $this->t('Invisible'),
        'attribute' => $this->t('Attribute'),
      ],
      '#description' => $this->t('Determines the placement of the title.'),
    ];
    $form['form']['display_container']['description_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Description display'),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        'before' => $this->t('Before'),
        'after' => $this->t('After'),
        'invisible' => $this->t('Invisible'),
        'tooltip' => $this->t('Tooltip'),
      ],
      '#description' => $this->t('Determines the placement of the description.'),
    ];
    $form['form']['field_container'] = $this->getFormInlineContainer();
    $form['form']['field_container']['field_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field prefix'),
      '#description' => $this->t('Text or code that is placed directly in front of the input. This can be used to prefix an input with a constant string. Examples: $, #, -.'),
      '#size' => 10,
    ];
    $form['form']['field_container']['field_suffix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Field suffix'),
      '#description' => $this->t('Text or code that is placed directly after the input. This can be used to add a unit to an input. Examples: lb, kg, %.'),
      '#size' => 10,
    ];
    $form['form']['length_container'] = $this->getFormInlineContainer();
    $form['form']['length_container']['minlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Minlength'),
      '#description' => $this->t('The element may still be empty unless it is required.'),
      '#min' => 1,
      '#size' => 4,
    ];    $form['form']['length_container']['maxlength'] = [
      '#type' => 'number',
      '#title' => $this->t('Maxlength'),
      '#description' => $this->t('Leaving blank will use the default maxlength.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['size_container'] = $this->getFormInlineContainer();
    $form['form']['size_container']['size'] = [
      '#type' => 'number',
      '#title' => $this->t('Size'),
      '#description' => $this->t('Leaving blank will use the default size.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['size_container']['rows'] = [
      '#type' => 'number',
      '#title' => $this->t('Rows'),
      '#description' => $this->t('Leaving blank will use the default rows.'),
      '#min' => 1,
      '#size' => 4,
    ];
    $form['form']['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#description' => $this->t('The placeholder will be shown in the element until the user starts entering a value.'),
    ];
    $form['form']['autocomplete'] = [
      '#type' => 'select',
      '#title' => $this->t('Autocomplete'),
      '#options' => [
        'on' => $this->t('On'),
        'off' => $this->t('Off'),
      ],
      '#description' => $this->t('Setting autocomplete to off will disable autocompletion for this element.'),
    ];
    $form['form']['disabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disabled'),
      '#description' => $this->t('Make this element non-editable with the value <strong>ignored</strong>. Useful for displaying default value. Changeable via JavaScript.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    $form['form']['readonly'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Readonly'),
      '#description' => $this->t('Make this element non-editable with the value <strong>submitted</strong>. Useful for displaying default value. Changeable via JavaScript.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    $form['form']['prepopulate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prepopulate'),
      '#description' => $this->t('Allow element to be populated using query string parameters'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    // Disabled check element when prepopulate is enabled for all elements.
    if ($webform->getSetting('form_prepopulate') && $this->hasProperty('prepopulate')) {
      $form['form']['prepopulate_disabled'] = [
        '#description' => $this->t('Prepopulation is enabled for all form elements.'),
        '#value' => TRUE,
        '#disabled' => TRUE,
        '#access' => TRUE,
      ] + $form['form']['prepopulate'];
      $form['form']['prepopulate']['#value'] = FALSE;
      $form['form']['prepopulate']['#access'] = FALSE;
    }
    $form['form']['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Open'),
      '#description' => $this->t('Contents should be visible (open) to the user.'),
      '#return_value' => TRUE,
      '#weight' => 50,
    ];
    $default_icheck = $this->configFactory->get('webform.settings')->get('element.default_icheck');
    $form['form']['icheck'] = [
      '#type' => 'select',
      '#title' => 'Enhance using iCheck',
      '#description' => $this->t('Replaces @type element with jQuery <a href=":href">iCheck</a> boxes.', ['@type' => Unicode::strtolower($this->getPluginLabel()), ':href' => 'http://icheck.fronteed.com/']),
      '#empty_option' => $this->t('- Default -'),
      '#options' => [
        (string) $this->t('Minimal') => [
          'minimal' => $this->t('Minimal: Black'),
          'minimal-grey' => $this->t('Minimal: Grey'),
          'minimal-yellow' => $this->t('Minimal: Yellow'),
          'minimal-orange' => $this->t('Minimal: Orange'),
          'minimal-red' => $this->t('Minimal: Red'),
          'minimal-pink' => $this->t('Minimal: Pink'),
          'minimal-purple' => $this->t('Minimal: Purple'),
          'minimal-blue' => $this->t('Minimal: Blue'),
          'minimal-green' => $this->t('Minimal: Green'),
          'minimal-aero' => $this->t('Minimal: Aero'),
        ],
        (string) $this->t('Square') => [
          'square' => $this->t('Square: Black'),
          'square-grey' => $this->t('Square: Grey'),
          'square-yellow' => $this->t('Square: Yellow'),
          'square-orange' => $this->t('Square: Orange'),
          'square-red' => $this->t('Square: Red'),
          'square-pink' => $this->t('Square: Pink'),
          'square-purple' => $this->t('Square: Purple'),
          'square-blue' => $this->t('Square: Blue'),
          'square-green' => $this->t('Square: Green'),
          'square-aero' => $this->t('Square: Aero'),
        ],
        (string) $this->t('Flat') => [
          'flat' => $this->t('Flat: Black'),
          'flat-grey' => $this->t('Flat: Grey'),
          'flat-yellow' => $this->t('Flat: Yellow'),
          'flat-orange' => $this->t('Flat: Orange'),
          'flat-red' => $this->t('Flat: Red'),
          'flat-pink' => $this->t('Flat: Pink'),
          'flat-purple' => $this->t('Flat: Purple'),
          'flat-blue' => $this->t('Flat: Blue'),
          'flat-green' => $this->t('Flat: Green'),
          'flat-aero' => $this->t('Flat: Aero'),
        ],
        (string) $this->t('Line') => [
          'line' => $this->t('Line: Black'),
          'line-grey' => $this->t('Line: Grey'),
          'line-yellow' => $this->t('Line: Yellow'),
          'line-orange' => $this->t('Line: Orange'),
          'line-red' => $this->t('Line: Red'),
          'line-pink' => $this->t('Line: Pink'),
          'line-purple' => $this->t('Line: Purple'),
          'line-blue' => $this->t('Line: Blue'),
          'line-green' => $this->t('Line: Green'),
          'line-aero' => $this->t('Line: Aero'),
        ],
      ],
    ];
    if ($this->librariesManager->isExcluded('jquery.icheck')) {
      $form['form']['icheck']['#access'] = FALSE;
    }
    if ($default_icheck) {
      $icheck_options = OptGroup::flattenOptions($form['form']['icheck']['#options']);
      $form['form']['icheck']['#description'] .= '<br /><br />' . $this->t("Leave blank to use the default iCheck style. Select 'None' to display the default HTML element.");
      $form['form']['icheck']['#description'] .= '<br /><br />' . $this->t('Defaults to: %value', ['%value' => $icheck_options[$default_icheck]]);
      $form['form']['icheck']['#options']['none'] = $this->t('None');
    }

    /* Validation */

    // Placeholder webform elements with #options.
    // @see \Drupal\webform\Plugin\WebformElement\OptionsBase::form
    $form['options'] = [];
    $form['options_other'] = [];

    $form['validation'] = [
      '#type' => 'details',
      '#title' => $this->t('Form validation'),
    ];
    $form['validation']['required_container'] = [
      '#type' => 'container',
    ];
    $form['validation']['required_container']['required'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Required'),
      '#description' => $this->t('Check this option if the user must enter a value.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['required_container']['required_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Required message'),
      '#description' => $this->t('If set, this message will be used when a required webform element is empty, instead of the default "Field x is required." message.'),
      '#states' => [
        'visible' => [
          ':input[name="properties[required]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['validation']['unique_container'] = $this->getFormInlineContainer();
    $form['validation']['unique_container']['unique'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique'),
      '#description' => $this->t('Check that all entered values for this element are unique.'),
      '#return_value' => TRUE,
    ];
    $form['validation']['unique_container']['unique_entity'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique per entity'),
      '#description' => $this->t('Check that entered values for this element is unique for the current source entity.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ['input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['validation']['unique_container']['unique_user'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique per user'),
      '#description' => $this->t('Check that entered values for this element are unique for the current user.'),
      '#return_value' => TRUE,
      '#states' => [
        'visible' => [
          ['input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];
    $form['validation']['unique_error'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Unique message'),
      '#description' => $this->t('If set, this message will be used when an element\'s value are not unique, instead of the default "@message" message.', ['@message' => $this->t('The value %value has already been submitted once for the %name element. You may have already submitted this webform, or you need to use a different value.')]),
      '#states' => [
        'visible' => [
          [':input[name="properties[unique]"]' => ['checked' => TRUE]],
        ],
      ],
    ];

    /* Flexbox item */

    $form['flex'] = [
      '#type' => 'details',
      '#title' => $this->t('Flexbox item'),
      '#description' => $this->t('Learn more about using <a href=":href">flexbox layouts</a>.', [':href' => 'http://www.w3schools.com/css/css3_flexbox.asp']),
    ];
    $flex_range = range(0, 12);
    $form['flex']['flex'] = [
      '#type' => 'select',
      '#title' => $this->t('Flex'),
      '#description' => $this->t('The flex property specifies the length of the item, relative to the rest of the flexible items inside the same container.') . '<br /><br />' .
      $this->t('Defaults to: %value', ['%value' => 1]),
      '#options' => [0 => $this->t('0 (none)')] + array_combine($flex_range, $flex_range),
    ];

    /**************************************************************************/
    // Conditions.
    /**************************************************************************/

    /* Conditional logic */

    $form['conditional_logic'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Conditional logic'),
    ];
    $form['conditional_logic']['states'] = [
      '#type' => 'webform_element_states',
      '#state_options' => $this->getElementStateOptions(),
      '#selector_options' => $webform->getElementsSelectorOptions(),
    ];
    if ($this->hasProperty('states') && $this->hasProperty('required')) {
      $form['conditional_logic']['states_required_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Please note when an element is hidden it will not be required.'),
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[required]"]' => ['checked' => TRUE],
          ],
        ],
      ];
    }

    /**************************************************************************/
    // Advanced.
    /**************************************************************************/

    /* Default value */

    $form['default'] = [
      '#type' => 'details',
      '#title' => $this->t('Default value'),
    ];
    if ($this->isComposite()) {
      $form['default']['default_value'] = [
        '#type' => 'webform_codemirror',
        '#mode' => 'yaml',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the webform element.'),
      ];
    }
    else {
      $form['default']['default_value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Default value'),
        '#description' => $this->t('The default value of the webform element.'),
        '#maxlength' => NULL,
      ];
    }
    if ($this->hasProperty('multiple')) {
      $form['default']['default_value']['#description'] .= ' ' . $this->t('For multiple options, use commas to separate multiple defaults.');
    }

    // Multiple.
    $form['multiple'] = [
      '#type' => 'details',
      '#title' => $this->t('Multiple settings'),
      '#states' => [
        'invisible' => [
          ':input[name="properties[multiple][container][cardinality]"]' => ['!value' => -1],
          ':input[name="properties[multiple][container][cardinality_number]"]' => ['value' => 1],
        ],
      ],
    ];
    $form['multiple']['multiple__header'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Display elements in table columns'),
      '#description' => $this->t("If checked, the composite sub-element titles will be displayed as the table header labels."),
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__header_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Table header label'),
      '#description' => $this->t('This is used as the table header for this webform element when displaying multiple values.'),
    ];
    $form['multiple']['multiple__label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item label'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#description' => $this->t('This is used as the item label for this webform element when displaying multiple values.'),
    ];
    $form['multiple']['multiple__labels'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item labels'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#description' => $this->t('This is used as the items label for this webform element when displaying multiple values.'),
    ];
    $form['multiple']['multiple__min_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Minimum amount of items'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 20,
    ];
    $form['multiple']['multiple__empty_items'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of empty items'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#required' => TRUE,
      '#min' => 0,
      '#max' => 20,
    ];
    $form['multiple']['multiple__add_more'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of add more items'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#required' => TRUE,
      '#min' => 1,
      '#max' => 20,
    ];
    $form['multiple']['multiple__sorting'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to sort elements.'),
      '#description' => $this->t('If unchecked, the elements will no longer be sortable.'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#return_value' => TRUE,
    ];
    $form['multiple']['multiple__operations'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to add/remove elements.'),
      '#description' => $this->t('If unchecked, the add/remove (+/x) buttons will be removed from each table row.'),
      '#attributes' => ['data-webform-states-no-clear' => TRUE],
      '#return_value' => TRUE,
    ];

    /* Wrapper attributes */

    $form['wrapper_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Wrapper attributes'),
    ];
    $form['wrapper_attributes']['wrapper_attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Wrapper'),
      '#class__description' => $this->t("Apply classes to the element's wrapper around both the field and its label. Select 'custom...' to enter custom classes."),
      '#style__description' => $this->t("Apply custom styles to the element's wrapper around both the field and its label."),
      '#attributes__description' => $this->t("Enter additional attributes to be added the element's wrapper."),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.wrapper_classes'),
    ];

    /* Element attributes */

    $form['element_attributes'] = [
      '#type' => 'details',
      '#title' => $this->t('Element attributes'),
    ];
    $form['element_attributes']['attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Element'),
      '#classes' => $this->configFactory->get('webform.settings')->get('element.classes'),
    ];

    /* Submission display */
    $has_edit_twig_access = TwigExtension::hasEditTwigAccess();

    $form['display'] = [
      '#type' => 'details',
      '#title' => $this->t('Submission display'),
    ];
    // Item.
    $form['display']['item'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Single item'),
    ];
    $form['display']['item']['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Item format'),
      '#description' => $this->t('Select how a single value is displayed.'),
      '#options' => WebformOptionsHelper::appendValueToText($this->getItemFormats()),
    ];
    $format = isset($element_properties['format']) ? $element_properties['format'] : NULL;
    $format_custom = ($has_edit_twig_access || $format === 'custom');
    if ($format_custom) {
      $form['display']['item']['format']['#options'] += ['custom' => $this->t('Custom...')];
    }
    $custom_states = [
      'visible' => [':input[name="properties[format]"]' => ['value' => 'custom']],
      'required' => [':input[name="properties[format]"]' => ['value' => 'custom']],
    ];
    $form['display']['item']['format_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Item format custom HTML'),
      '#description' => $this->t('The HTML to display for a single element value. You may include HTML or <a href=":href">Twig</a>. You may enter data from the submission as per the "Replacement patterns" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $custom_states,
      '#access' => $format_custom,
    ];
    $form['display']['item']['format_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Item format custom Text'),
      '#description' => $this->t('The text to display for a single element value. You may include <a href=":href">Twig</a>. You may enter data from the submission as per the "Replacement patterns" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $custom_states,
      '#access' => $format_custom,
    ];
    $items = [
      'value' => '{{ value }}',
    ];
    $formats = $this->getItemFormats();
    foreach ($formats as $format_name => $format) {
      if (is_array($format)) {
        foreach ($format as $sub_format_name => $sub_format) {
          $items["item['$sub_format_name']"] = "{{ item['$sub_format_name'] }}";
        }
      }
      else {
        $items["item.$format_name"] = "{{ item.$format_name }}";
      }
    }
    $form['display']['item']['patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacement patterns'),
      '#access' => $has_edit_twig_access,
      '#states' => $custom_states,
      '#value' => [
        'description' => [
          '#markup' => '<p>' . $this->t('The following replacement tokens are available for single element value.') . '</p>',
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ],
    ];

    // Items.
    $form['display']['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Multiple items'),
      '#states' => [
        'visible' => [
          [':input[name="properties[multiple][container][cardinality]"]' => ['value' => '-1']],
          'or',
          [':input[name="properties[multiple][container][cardinality_number]"]' => ['!value' => 1]],
        ],
      ],
    ];
    $form['display']['items']['format_items'] = [
      '#type' => 'select',
      '#title' => $this->t('Items format'),
      '#description' => $this->t('Select how multiple values are displayed.'),
      '#options' => WebformOptionsHelper::appendValueToText($this->getItemsFormats()),
    ];
    $format_items = isset($element_properties['format_items']) ? $element_properties['format_items'] : NULL;
    $format_items_custom = ($has_edit_twig_access || $format_items === 'custom');
    if ($format_items_custom) {
      $form['display']['items']['format_items']['#options'] += ['custom' => $this->t('Custom...')];
    }
    $custom_states = [
      'visible' => [':input[name="properties[format_items]"]' => ['value' => 'custom']],
      'required' => [':input[name="properties[format_items]"]' => ['value' => 'custom']],
    ];
    $form['display']['items']['format_items_html'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Items format custom HTML'),
      '#description' => $this->t('The HTML to display for multiple element values. You may include HTML or <a href=":href">Twig</a>. You may enter data from the submission as per the "Replacement patterns" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $custom_states,
      '#access' => $format_items_custom,
    ];
    $form['display']['items']['format_items_text'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'twig',
      '#title' => $this->t('Items format custom Text'),
      '#description' => $this->t('The text to display for multiple element values. You may include <a href=":href">Twig</a>. You may enter data from the submission as per the "Replacement patterns" below.', [':href' => 'http://twig.sensiolabs.org/documentation']),
      '#states' => $custom_states,
      '#access' => $format_items_custom,
    ];
    $items = [
      '{{ value }}',
      '{{ items }}',
    ];
    $form['display']['items']['patterns'] = [
      '#type' => 'details',
      '#title' => $this->t('Replacement patterns'),
      '#access' => $has_edit_twig_access,
      '#states' => $custom_states,
      '#value' => [
        'description' => [
          '#markup' => '<p>' . $this->t('The following replacement tokens are available for multiple element values.') . '</p>',
        ],
        'items' => [
          '#theme' => 'item_list',
          '#items' => $items,
        ],
      ],
    ];

    /* Administration */

    $form['admin'] = [
      '#type' => 'details',
      '#title' => $this->t('Administration'),
    ];
    $form['admin']['private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Private'),
      '#description' => $this->t('Private elements are shown only to users with results access.'),
      '#weight' => 50,
      '#return_value' => TRUE,
    ];
    $form['admin']['admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Admin title'),
      '#description' => $this->t('The admin title will always be displayed when managing elements and viewing & downloading submissions.') .
        '<br/>' .
        $this->t("If an element's title is hidden, the element's admin title will be displayed when viewing a submission."),
    ];

    /**************************************************************************/
    // Access.
    /**************************************************************************/

    /* Access */

    $operations = [
      'create' => [
        '#title' => $this->t('Create webform submission'),
        '#description' => $this->t('Select roles and users that should be able to populate this element when creating a new submission.'),
        '#open' => TRUE,
      ],
      'update' => [
        '#title' => $this->t('Update webform submission'),
        '#description' => $this->t('Select roles and users that should be able to update this element when updating an existing submission.'),
        '#open' => FALSE,
      ],
      'view' => [
        '#title' => $this->t('View webform submission'),
        '#description' => $this->t('Select roles and users that should be able to view this element when viewing a submission.'),
        '#open' => FALSE,
      ],
    ];

    $form['access'] = [
      '#type' => 'container',
    ];
    if (!$this->currentUser->hasPermission('administer webform') && !$this->currentUser->hasPermission('administer webform element access')) {
      $form['access']['#access'] = FALSE;
    }
    foreach ($operations as $operation => $operation_element) {
      $form['access']['access_' . $operation] = $operation_element + [
        '#type' => 'details',
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_roles'] = [
        '#type' => 'webform_roles',
        '#title' => $this->t('Roles'),
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_users'] = [
        '#type' => 'webform_users',
        '#title' => $this->t('Users'),
      ];
      $form['access']['access_' . $operation]['access_' . $operation . '_permissions'] = [
        '#type' => 'webform_permissions',
        '#title' => $this->t('Permissions'),
        '#multiple' => TRUE,
        '#select2' => TRUE,
      ];
    }

    /**************************************************************************/

    // Disable #multiple if the element has submission data.
    if (!$form_object->isNew() && $this->hasProperty('multiple')) {
      $element_key = $form_object->getKey();
      if ($this->submissionStorage->hasSubmissionValue($webform, $element_key)) {
        $form['element']['multiple']['#disabled'] = TRUE;
        $form['element']['multiple']['#description'] = '<em>' . $this->t('There is data for this element in the database. This setting can no longer be changed.') . '</em>';
      }
    }

    // Add warning to all password elements that are stored in the database.
    if (strpos($this->pluginId, 'password') !== FALSE && !$webform->getSetting('results_disabled')) {
      $form['element']['password_message'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Webform submissions store passwords as plain text.') . ' ' .
          $this->t('<a href=":href">Encryption</a> should be enabled for this element.', [':href' => 'https://www.drupal.org/project/webform_encrypt']),
        '#access' => TRUE,
        '#weight' => -100,
        '#message_close' => TRUE,
        '#message_storage' => WebformMessage::STORAGE_SESSION,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_properties = $this->getDefaultProperties();

    // Unset 'format_items' if the element does not support multiple values.
    if (!$this->supportsMultipleValues()) {
      unset(
        $default_properties['format_items'],
        $default_properties['format_items_html'],
        $default_properties['format_items_text']
      );
    }

    $element_properties = WebformArrayHelper::removePrefix($this->configuration) + $default_properties;

    // Set default and element properties.
    // Note: Storing this information in the webform's state allows modules to view
    // and alter this information using webform alteration hooks.
    $form_state->set('default_properties', $default_properties);
    $form_state->set('element_properties', $element_properties);

    $form = $this->form($form, $form_state);

    // Get element properties which can be altered by WebformElementHandlers.
    // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
    $element_properties = $form_state->get('element_properties');

    // Copy element properties to custom properties which will be determined
    // as the default values are set.
    $custom_properties = $element_properties;

    // Populate the webform.
    $this->setConfigurationFormDefaultValueRecursive($form, $custom_properties);

    // Set fieldset weights so that they appear first.
    foreach ($form as &$element) {
      if (is_array($element) && !isset($element['#weight']) && isset($element['#type']) && $element['#type'] == 'fieldset') {
        $element['#weight'] = -20;
      }
    }

    // Store 'type' as a hardcoded value and make sure it is always first.
    // Also always remove the 'webform_*' prefix from the type name.
    if (isset($custom_properties['type'])) {
      $form['type'] = [
        '#type' => 'value',
        '#value' => $custom_properties['type'],
        '#parents' => ['properties', 'type'],
      ];
      unset($custom_properties['type']);
    }

    // Allow custom properties (i.e. #attributes) to be added to the element.
    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t('Custom settings'),
      '#open' => $custom_properties ? TRUE : FALSE,
      '#access' => $this->currentUser->hasPermission('edit webform source'),
    ];
    if ($api_url = $this->getPluginApiUrl()) {
      $t_args = [
        ':href' => $api_url->toString(),
        '%label' => $this->getPluginLabel(),
      ];
      $form['custom']['#description'] = $this->t('Read the %label element\'s <a href=":href">API documentation</a>.', $t_args);
    }

    $form['custom']['properties'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom properties'),
      '#description' => $this->t('Properties do not have to be prepended with a hash (#) character, the hash character will be automatically added to the custom properties.') .
        '<br /><br />' .
        $this->t('These properties and callbacks are not allowed: @properties', ['@properties' => WebformArrayHelper::toString(WebformArrayHelper::addPrefix(WebformElementHelper::$ignoredProperties))]),
      '#default_value' => $custom_properties ,
      '#parents' => ['properties', 'custom'],
    ];

    $form['token_tree_link'] = $this->tokenManager->buildTreeLink();

    $this->tokenManager->elementValidate($form);

    // Set custom properties.
    // Note: Storing this information in the webform's state allows modules to
    // view and alter this information using webform alteration hooks.
    $form_state->set('custom_properties', $custom_properties);

    return $this->buildConfigurationFormTabs($form, $form_state);
  }

  /**
   * Get form--inline container which is used for side-by-side element layout.
   *
   * @return array
   *   A container element with .form--inline class if inline help text is
   *   enabled.
   */
  protected function getFormInlineContainer() {
    $help_enabled = $this->configFactory->get('webform.settings')->get('ui.description_help');
    return [
      '#type' => 'container',
      '#attributes' => ($help_enabled) ? ['class' => ['form--inline', 'clearfix', 'webform-ui-element-form-inline--input']] : [],
    ];
  }

  /**
   * Build configuration form tabs.
   *
   * @param array $form
   *   An associative array containing the initial structure of the plugin form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The plugin form with tabs.
   */
  protected function buildConfigurationFormTabs(array $form, FormStateInterface $form_state) {
    $tabs = [
      'conditions' => [
        'title' => $this->t('Conditions'),
        'elements' => [
          'conditional_logic',
        ],
        'weight' => 10,
      ],
      'advanced' => [
        'title' => $this->t('Advanced'),
        'elements' => [
          'default',
          'multiple',
          'wrapper_attributes',
          'element_attributes',
          'display',
          'admin',
          'custom',
        ],
        'weight' => 20,
      ],
      'access' => [
        'title' => $this->t('Access'),
        'elements' => [
          'access',
        ],
        'weight' => 30,
      ],
    ];
    return WebformFormHelper::buildTabs($form, $tabs, $form_state->get('active_tab'));
  }

  /**
   * Set configuration webform default values recursively.
   *
   * @param array $form
   *   A webform render array.
   * @param array $element_properties
   *   The element's properties without hash prefix. Any property that is found
   *   in the webform will be populated and unset from
   *   $element_properties array.
   *
   * @return bool
   *   TRUE is the webform has any inputs.
   */
  protected function setConfigurationFormDefaultValueRecursive(array &$form, array &$element_properties) {
    $has_input = FALSE;

    foreach ($form as $property_name => &$property_element) {
      // Skip all properties.
      if (Element::property($property_name)) {
        continue;
      }

      // Skip Entity reference element 'selection_settings'.
      // @see \Drupal\webform\Plugin\WebformElement\WebformEntityReferenceTrait::form
      // @todo Fix entity reference Ajax and move code WebformEntityReferenceTrait.
      if (!empty($property_element['#tree']) && $property_name == 'selection_settings') {
        unset($element_properties[$property_name]);
        $property_element['#parents'] = ['properties', $property_name];
        $has_input = TRUE;
        continue;
      }

      // Determine if the property element is an input using the webform element
      // manager.
      // Note: #access is used to protect inputs and containers that should
      // always be visible.
      $is_input = $this->elementManager->getElementInstance($property_element)->isInput($property_element);
      if ($is_input) {
        if (isset($element_properties[$property_name])) {
          // If this property exists, then set its default value.
          $this->setConfigurationFormDefaultValue($form, $element_properties, $property_element, $property_name);
          $has_input = TRUE;
        }
        elseif (empty($form[$property_name]['#access'])) {
          // Else completely remove the property element from the webform.
          unset($form[$property_name]);
        }
      }
      else {
        // Recurse down this container and see if it's children have inputs.
        $container_has_input = $this->setConfigurationFormDefaultValueRecursive($property_element, $element_properties);
        if ($container_has_input) {
          $has_input = TRUE;
        }
        elseif (empty($form[$property_name]['#access'])) {
          unset($form[$property_name]);
        }
      }
    }

    return $has_input;
  }

  /**
   * Set an element's configuration webform element default value.
   *
   * @param array $form
   *   An element's configuration webform.
   * @param array $element_properties
   *   The element's properties without hash prefix.
   * @param array $property_element
   *   The webform input used to set an element's property.
   * @param string $property_name
   *   THe property's name.
   */
  protected function setConfigurationFormDefaultValue(array &$form, array &$element_properties, array &$property_element, $property_name) {
    $default_value = $element_properties[$property_name];
    $type = (isset($property_element['#type'])) ? $property_element['#type'] : NULL;

    switch ($type) {
      case 'entity_autocomplete':
        $target_type = $property_element['#target_type'];
        $target_storage = $this->entityTypeManager->getStorage($target_type);
        if (!empty($property_element['#tags'])) {
          $property_element['#default_value'] = ($default_value) ? $target_storage->loadMultiple($default_value) : [];
        }
        else {
          $property_element['#default_value'] = ($default_value) ? $target_storage->load($default_value) : NULL;
        }
        break;

      case 'radios':
      case 'select':
        // Handle invalid default_value throwing
        // "An illegal choice has been detected..." error.
        if (!is_array($default_value) && isset($property_element['#options'])) {
          $flattened_options = OptGroup::flattenOptions($property_element['#options']);
          if (!isset($flattened_options[$default_value])) {
            $default_value = NULL;
          }
        }
        $property_element['#default_value'] = $default_value;
        break;

      default:
        // Convert default_value array into a comma delimited list.
        // This is applicable to elements that support #multiple #options.
        if (is_array($default_value) && $property_name == 'default_value' && !$this->isComposite()) {
          $property_element['#default_value'] = implode(', ', $default_value);
        }
        elseif (is_bool($default_value) && $property_name == 'default_value') {
          $property_element['#default_value'] = $default_value ? 1 : 0;
        }
        else {
          $property_element['#default_value'] = $default_value;
        }
        break;

    }

    $property_element['#parents'] = ['properties', $property_name];
    unset($element_properties[$property_name]);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $properties = $this->getConfigurationFormProperties($form, $form_state);

    $ignored_properties = WebformElementHelper::getIgnoredProperties($properties);
    foreach ($ignored_properties as $ignored_property => $ignored_message) {
      // Display custom messages.
      if ($ignored_property != $ignored_message) {
        unset($ignored_properties[$ignored_property]);
        $form_state->setErrorByName('custom', $ignored_message);
      }
    }

    // Display ignored properties message.
    if ($ignored_properties) {
      $t_args = [
        '@properties' => WebformArrayHelper::toString($ignored_properties),
      ];
      $form_state->setErrorByName('custom', $this->t('Element contains ignored/unsupported properties: @properties', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Generally, elements will not be processing any submitted properties.
    // It is possible that a custom element might need to call a third-party API
    // to 'register' the element.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationFormProperties(array &$form, FormStateInterface $form_state) {
    $element_properties = $form_state->getValues();

    // Get default properties so that they can be unset below.
    $default_properties = $form_state->get('default_properties');

    // Get custom properties.
    if (isset($element_properties['custom'])) {
      if (is_array($element_properties['custom'])) {
        $element_properties += $element_properties['custom'];
      }
      unset($element_properties['custom']);
    }

    // Remove all hash prefixes so that we can filter out any default
    // properties.
    WebformArrayHelper::removePrefix($element_properties);

    // Build a temp element used to see if multiple value and/or composite
    // elements need to be supported.
    $element = WebformArrayHelper::addPrefix($element_properties);
    foreach ($element_properties as $property_name => $property_value) {
      if (!isset($default_properties[$property_name])) {
        continue;
      }

      $this->getConfigurationFormProperty($element_properties, $property_name, $property_value, $element);

      // Unset element property that matched the default property.
      switch ($property_name) {
        case 'multiple':
          // The #multiple property element is converted to the correct datatype
          // so we are looking for 'strict equality' (===).
          // This prevents #multiple: 2 from being interpeted as TRUE.
          // @see \Drupal\webform\Element\WebformElementMultiple::validateWebformElementMultiple
          // @see \Drupal\webform\Plugin\WebformElement\Checkboxes::getDefaultProperties
          if ($default_properties[$property_name] === $element_properties[$property_name]) {
            unset($element_properties[$property_name]);
          }
          break;

        default:
          // Most elements properties are strings or numbers and we need to use
          // 'type-converting equality' (==) because all numbers are posted
          // back to the server as strings.
          if ($default_properties[$property_name] == $element_properties[$property_name]) {
            unset($element_properties[$property_name]);
          }

          // Cast data types (except #multiple).
          if (is_bool($default_properties[$property_name]) && isset($element_properties[$property_name])) {
            $element_properties[$property_name] = (bool) $element_properties[$property_name];
          }
          break;
      }
    }

    // Make sure #type is always first.
    if (isset($element_properties['type'])) {
      $element_properties = ['type' => $element_properties['type']] + $element_properties;
    }

    return WebformArrayHelper::addPrefix($element_properties);
  }

  /**
   * Get configuration property value.
   *
   * @param array $properties
   *   An associative array of submitted properties.
   * @param string $property_name
   *   The property's name.
   * @param mixed $property_value
   *   The property's value.
   * @param array $element
   *   The element whose properties are being updated.
   */
  protected function getConfigurationFormProperty(array &$properties, $property_name, $property_value, array $element) {
    if ($property_name == 'default_value' && is_string($property_value) && $property_value && $this->hasMultipleValues($element)) {
      $properties[$property_name] = preg_split('/\s*,\s*/', $property_value);
    }
  }

}
