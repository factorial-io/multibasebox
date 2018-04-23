<?php

/**
 * @file
 * Hooks related to Webform module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformElement.
 *
 * @param array $definitions
 *   The array of webform handlers, keyed on the machine-readable element name.
 */
function hook_webform_element_info_alter(array &$definitions) {

}

/**
 * Alter the information provided in \Drupal\webform\Annotation\WebformHandler.
 *
 * @param array $handlers
 *   The array of webform handlers, keyed on the machine-readable handler name.
 */
function hook_webform_handler_info_alter(array &$handlers) {

}

/**
 * Alter definition of WebformSourceEntity plugins.
 *
 * @param array $definitions
 *   The array of plugin definitions.
 */
function hook_webform_source_entity_info_alter(array &$definitions) {
  if (isset($definitions['some_plugin_whose_weight_i_wanna_change'])) {
    $definitions['some_plugin_whose_weight_i_wanna_change']['weight'] = -1000;
  }
}

/**
 * Alter webform elements.
 *
 * @param array $element
 *   The webform element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array containing the following key-value pairs:
 *   - form: The form structure to which elements is being attached.
 *
 * @see \Drupal\webform\WebformSubmissionForm::prepareElements()
 * @see hook_webform_element_ELEMENT_TYPE_alter()
 */
function hook_webform_element_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Code here acts on all elements included in a webform.
  /** @var \Drupal\webform\WebformSubmissionForm $form_object */
  $form_object = $form_state->getFormObject();
  /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
  $webform_submission = $form_object->getEntity();
  /** @var \Drupal\webform\WebformInterface $webform */
  $webform = $webform_submission->getWebform();

  // Add custom data attributes to all elements.
  $element['#attributes']['data-custom'] = '{custom data goes here}';
}

/**
 * Alter webform elements for a specific type.
 *
 * Modules can implement hook_webform_element_ELEMENT_TYPE_alter() to
 * modify a specific webform element, rather than using
 * hook_webform_element_alter() and checking the element type.
 *
 * @param array $element
 *   The webform element.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 * @param array $context
 *   An associative array. See hook_field_widget_alter() for the structure
 *   and content of the array.
 *
 * @see \Drupal\webform\WebformSubmissionForm::prepareElements()
 * @see hook_webform_element_alter(()
 */
function hook_webform_element_ELEMENT_TYPE_alter(array &$element, \Drupal\Core\Form\FormStateInterface $form_state, array $context) {
  // Add custom data attributes to a specific element type.
  $element['#attributes']['data-custom'] = '{custom data goes here}';

  // Attach a custom library to the element type.
  $element['#attached']['library'][] = 'MODULE/MODULE.element.ELEMENT_TYPE';
}

/**
 * Alter webform options.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The webform element that the options is for.
 * @param string $options_id
 *   The webform options id. Set to NULL if the options are custom.
 */
function hook_webform_options_alter(array &$options, array &$element, $options_id = NULL) {

}

/**
 * Alter webform options by id.
 *
 * @param array $options
 *   An associative array of options.
 * @param array $element
 *   The webform element that the options is for.
 */
function hook_webform_options_WEBFORM_OPTIONS_ID_alter(array &$options, array &$element) {

}

/**
 * Perform alterations before a webform submission form is rendered.
 *
 * This hook is identical to hook_form_alter() but allows the
 * hook_webform_submission_form_alter() function to be stored in a dedicated
 * include file and it also allows the Webform module to implement webform alter
 * logic on another module's behalf.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form. The arguments that
 *   \Drupal::formBuilder()->getForm() was originally called with are available
 *   in the array $form_state->getBuildInfo()['args'].
 * @param string $form_id
 *   String representing the webform's id.
 *
 * @see webform.honeypot.inc
 * @see hook_form_BASE_FORM_ID_alter()
 * @see hook_form_FORM_ID_alter()
 *
 * @ingroup form_api
 */
function hook_webform_submission_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

}

/**
 * Perform alterations on webform admin third party settings form.
 *
 * This hook is identical to hook_form_alter() but allows contrib and custom
 * modules to define third party settings.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\webform\Form\WebformAdminSettingsForm
 * @see webform.honeypot.inc
 *
 * @ingroup form_api
 */
function hook_webform_admin_third_party_settings_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}

/**
 * Perform alterations on webform third party settings form.
 *
 * This hook is identical to hook_form_alter() but allows contrib and custom
 * modules to define third party settings.
 *
 * @param array $form
 *   Nested array of form elements that comprise the webform.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *   The current state of the form.
 *
 * @see \Drupal\webform\WebformEntitySettingsForm
 * @see webform.honeypot.inc
 *
 * @ingroup form_api
 */
function hook_webform_third_party_settings_form_alter(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

}

/**
 * Return information about external webform libraries.
 *
 * @internal
 * This hook will most likely be removed once there is a
 * stable release of Libraries API for Drupal 8.
 *
 * @see https://www.drupal.org/project/libraries
 * @see \Drupal\webform\WebformLibrariesManager::initLibraries
 */
function hook_webform_libraries_info() {

}

/**
 * Alter the webform module's libraries information.
 *
 * @internal
 * This hook will most likely be removed once there is a
 * stable release of Libraries API for Drupal 8.
 *
 * @see https://www.drupal.org/project/libraries
 * @see \Drupal\webform\WebformLibrariesManager::initLibraries
 */
function hook_webform_libraries_info_alter(&$libraries) {

}

/**
 * Collect extra webform help from modules.
 *
 * To help on-boarding new users, there is a robust help system developed. If
 * you would like to add extra webform help, you are free to implement this
 * hook.
 *
 * @return array
 *   Extra webform help your module is providing to the users. The return array
 *   should be keyed by help ID (a unique machine-name) and each sub array
 *   should have the following structure:
 *   - access: (bool) Whether the current user is granted access to this help.
 *     Defaults to TRUE.
 *   - routes: (array) Array of route names where your help should be displayed.
 *   - paths: (array) Array of paths where your help should be displayed. You
 *     can use any syntax supported by the "path.matcher" service.
 *   - video_id: (string) Optional video to include in the help message. Allowed
 *     values are the keys of WebformHelpManager::videos array.
 *   - attached: (array) Optional #attached array to attach to your help
 *     renderable array.
 *   - group: (string) Group where your help belongs. Allowed values are the
 *     keys of WebformHelpManager::groups array.
 *   - title: (string) Title of your help
 *   - content: (array) Renderable array of your help
 *   - message_id: (string) Optional message ID that will be supplied into
 *     'webform_message' element. You are free to use 'message_*' keys if you
 *     want to additionally display a message when your help is displayed. These
 *     keyes will be supplied into 'webform_message' element. Refer to the docs
 *     of this element for their meaning.
 *   - message_type: (string) Will be supplied into 'webform_message' element.
 *   - message_close: (bool) Will be supplied into 'webform_message' element.
 *   - message_storage: (string) Will be supplied into 'webform_message'
 *     element.
 */
function hook_webform_help_info() {
  $help = [];

  $help['my_custom_help'] = [
    'access' => \Drupal::currentUser()->hasPermission('my cool permission'),
    'routes' => [
      'my_module.route_where_i_show_this_help',
    ],
    'paths' => [
      '/path/where/*/i-wanna/show-help',
    ],
    'video_id' => 'blocks',
    'attached' => [],
    'group' => 'messages',
    'title' => t('Message: Webform UI Disabled'),
    'content' => t('Please enable the <strong>Webform UI</strong> module if you would like to add easily add and manage elements using a drag-n-drop user interface.'),
    'message_id' => '',
    'message_type' => 'warning',
    'message_close' => TRUE,
    'message_storage' => \Drupal\webform\Element\WebformMessage::STORAGE_STATE,
  ];

  return $help;
}

/**
 * Alter the webform help.
 *
 * @param array $help
 *   Webform help data as collected from hook_webform_help_info().
 */
function hook_webform_help_info_alter(array &$help) {
  if (isset($help['some_help_i_wanna_change'])) {
    $help['title'] = t('This is a really cool help message. Do read it thorough!');
  }
}

/**
 * Act on a custom message being displayed, closed or reset.
 *
 * @param string $operation
 *   closed: Returns TRUE if the message is closed.
 *   close: Sets the message's state to closed.
 *   reset: Resets the message's closed state.
 *
 * @param string $id
 *   The message id.
 *
 * @return mixed|boolean
 *   TRUE if message is closed, else NULL
 *
 * @internal
 *   This is an experimental hook whose definition may change.
 *
 * @see \Drupal\webform\Element\WebformMessage::isClosed
 * @see \Drupal\webform\Element\WebformMessage::setClosed
 * @see \Drupal\webform\Element\WebformMessage::resetClosed
 */
function hook_webform_message_custom($operation, $id) {
  // Handle 'webform_test_message_custom' defined in
  // webform.webform.test_element_message.yml.
  if ($id === 'webform_test_message_custom') {
    switch ($operation) {
      case 'closed':
        return \Drupal::state()->get($id, FALSE);

      case 'close':
        \Drupal::state()->set($id, TRUE);
        return NULL;

      case 'reset':
        \Drupal::state()->delete($id);
        return NULL;
    }
  }
}

/**
 * @} End of "addtogroup hooks".
 */
