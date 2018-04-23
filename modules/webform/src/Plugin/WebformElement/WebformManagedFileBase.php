<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Url as UrlGenerator;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Component\Transliteration\TransliterationInterface;
use Drupal\file\FileInterface;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\Component\Utility\Bytes;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\WebformLibrariesManagerInterface;
use Drupal\webform\WebformTokenManagerInterface;

/**
 * Provides a base class webform 'managed_file' elements.
 */
abstract class WebformManagedFileBase extends WebformElementBase {

  /**
   * List of blacklisted mime types that must be downloaded.
   *
   * @var array
   */
  static protected $blacklistedMimeTypes = [
    'application/pdf',
    'application/xml',
    'image/svg+xml',
    'text/html',
  ];

  /**
   * The 'file_system' service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The 'file.usage' service.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface
   */
  protected $fileUsage;

  /**
   * The 'transliteration' service.
   *
   * @var \Drupal\Component\Transliteration\TransliterationInterface
   */
  protected $transliteration;

  /**
   * The 'language_manager' service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * WebformManagedFileBase constructor.
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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\file\FileUsage\FileUsageInterface|NULL $file_usage
   *   The file usage service.
   * @param \Drupal\Component\Transliteration\TransliterationInterface $transliteration
   *   The transliteration service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,LoggerInterface $logger, ConfigFactoryInterface $config_factory, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, ElementInfoManagerInterface $element_info, WebformElementManagerInterface $element_manager, WebformTokenManagerInterface $token_manager, WebformLibrariesManagerInterface $libraries_manager, FileSystemInterface $file_system, $file_usage, TransliterationInterface $transliteration, LanguageManagerInterface $language_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $logger, $config_factory, $current_user, $entity_type_manager, $element_info, $element_manager, $token_manager, $libraries_manager);

    $this->fileSystem = $file_system;
    $this->fileUsage = $file_usage;
    $this->transliteration = $transliteration;
    $this->languageManager = $language_manager;
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
      $container->get('webform.libraries_manager'),
      $container->get('file_system'),
      // We soft depend on "file" module so this service might not be available.
      $container->has('file.usage') ? $container->get('file.usage') : NULL,
      $container->get('transliteration'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    $file_extensions = $this->getFileExtensions();
    $properties = parent::getDefaultProperties() + [
      'multiple' => FALSE,
      'max_filesize' => '',
      'file_extensions' => $file_extensions,
      'file_name' => '',
      'uri_scheme' => 'private',
      'sanitize' => FALSE,
      'button' => FALSE,
      'button__title' => '',
      'button__attributes' => [],
    ];
    // File uploads can't be prepopulated.
    unset($properties['prepopulate']);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsMultipleValues() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function hasMultipleWrapper() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isMultiline(array $element) {
    if ($this->hasMultipleValues($element)) {
      return TRUE;
    }
    else {
      return parent::isMultiline($element);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    if (!parent::isEnabled()) {
      return FALSE;
    }

    // Disable File element is there are no visible stream wrappers.
    $scheme_options = static::getVisibleStreamWrappers();
    return (empty($scheme_options)) ? FALSE : TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function displayDisabledWarning(array $element) {
    // Display standard disabled element warning.
    if (!parent::isEnabled()) {
      parent::displayDisabledWarning($element);
    }
    else {
      // Display 'managed_file' stream wrappers warning.
      $scheme_options = static::getVisibleStreamWrappers();
      $uri_scheme = $this->getUriScheme($element);
      if (!isset($scheme_options[$uri_scheme]) && $this->currentUser->hasPermission('administer webform')) {
        drupal_set_message($this->t('The \'File\' element is unavailable because a <a href="https://www.ostraining.com/blog/drupal/creating-drupal-8-private-file-system/">private files directory</a> has not been configured and public file uploads have not been enabled. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'), 'warning');
        $context = [
          'link' => Link::fromTextAndUrl($this->t('Edit'), UrlGenerator::fromRoute('<current>'))->toString(),
        ];
        $this->logger->notice("The 'File' element is unavailable because no stream wrappers are available", $context);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    // Track if this element has been processed because the work-around below
    // for 'Issue #2705471: Webform states File fields' which nests the
    // 'managed_file' element in a basic container, which triggers this element
    // to processed a second time.
    if (!empty($element['#webform_managed_file_processed'])) {
      return;
    }
    $element['#webform_managed_file_processed'] = TRUE;

    // Must come after #element_validate hook is defined.
    parent::prepare($element, $webform_submission);

    // Check if the URI scheme exists and can be used the upload location.
    $scheme_options = static::getVisibleStreamWrappers();
    $uri_scheme = $this->getUriScheme($element);
    if (!isset($scheme_options[$uri_scheme])) {
      $element['#access'] = FALSE;
      $this->displayDisabledWarning($element);
    }
    else {
      $element['#upload_location'] = $this->getUploadLocation($element, $webform_submission->getWebform());
    }

    $element['#upload_validators']['file_validate_size'] = [$this->getMaxFileSize($element)];
    $element['#upload_validators']['file_validate_extensions'] = [$this->getFileExtensions($element)];

    // Use custom validation callback so that File entities can be converted
    // into file ids (akk fids).
    // NOTE: Using array_splice() to make sure that self::validateManagedFile
    // is executed before all other validation hooks are executed but after
    // \Drupal\file\Element\ManagedFile::validateManagedFile.
    array_splice($element['#element_validate'], 1, 0, [[get_class($this), 'validateManagedFile']]);

    // Add file upload help to the element.
    $element['help'] = [
      '#theme' => 'file_upload_help',
      '#upload_validators' => $element['#upload_validators'],
      '#cardinality' => (empty($element['#multiple'])) ? 1 : $element['#multiple'],
      '#prefix' => '<div class="description">',
      '#suffix' => '</div>',
    ];

    // Issue #2705471: Webform states File fields.
    // Workaround: Wrap the 'managed_file' element in a basic container.
    if (!empty($element['#prefix'])) {
      $container = [
        '#prefix' => $element['#prefix'],
        '#suffix' => $element['#suffix'],
      ];
      unset($element['#prefix'], $element['#suffix']);
      $container[$element['#webform_key']] = $element + ['#webform_managed_file_processed' => TRUE];
      $element = $container;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDefaultValue(array &$element) {
    if (!empty($element['#default_value'])) {
      $element['#default_value'] = (array) $element['#default_value'];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatHtmlItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'id':
      case 'name':
      case 'url':
      case 'value':
      case 'raw':
        return $this->formatTextItem($element, $webform_submission, $options);

      case 'link':
        return [
          '#theme' => 'file_link',
          '#file' => $file,
        ];

      default:
        $theme = str_replace('webform_', 'webform_element_', $this->getPluginId());
        if (strpos($theme, 'webform_') !== 0) {
          $theme = 'webform_element_' . $theme;
        }
        return [
          '#theme' => $theme,
          '#element' => $element,
          '#value' => $value,
          '#options' => $options,
          '#file' => $file,
        ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    $value = $this->getValue($element, $webform_submission, $options);
    $file = $this->getFile($element, $value, $options);
    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'id':
        return $file->id();

      case 'name':
        return $file->getFilename();

      case 'url':
      case 'value':
      case 'raw':
      default:
        return file_create_url($file->getFileUri());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemDefaultFormat() {
    return 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
      'file' => $this->t('File'),
      'link' => $this->t('Link'),
      'url' => $this->t('URL'),
      'name' => $this->t('File name'),
      'id' => $this->t('File ID'),
    ];
  }

  /**
   * Get file.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return \Drupal\file\FileInterface
   *   A file.
   */
  protected function getFile(array $element, $value, array $options) {
    if (empty($value)) {
      return NULL;
    }
    if ($value instanceof FileInterface) {
      return $value;
    }

    return $this->entityTypeManager->getStorage('file')->load($value);
  }

  /**
   * Get files.
   *
   * @param array $element
   *   An element.
   * @param array|mixed $value
   *   A value.
   * @param array $options
   *   An array of options.
   *
   * @return array
   *   An associative array containing files.
   */
  protected function getFiles(array $element, $value, array $options) {
    if (empty($value)) {
      return [];
    }
    return $this->entityTypeManager->getStorage('file')->loadMultiple($value);
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    $title = $this->getAdminLabel($element);
    $name = $element['#webform_key'];
    $input = ($this->hasMultipleValues($element)) ? ":input[name=\"files[{$name}][]\"]" : ":input[name=\"files[{$name}]\"]";
    return [$input => $title . '  [' . $this->getPluginLabel() . ']'];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(array &$element, WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Get current value and original value for this element.
    $key = $element['#webform_key'];

    $original_data = $webform_submission->getOriginalData();
    $data = $webform_submission->getData();

    $value = isset($data[$key]) ? $data[$key] : [];
    $fids = (is_array($value)) ? $value : [$value];

    $original_value = isset($original_data[$key]) ? $original_data[$key] : [];
    $original_fids = (is_array($original_value)) ? $original_value : [$original_value];

    // Check the original submission fids and delete the old file upload.
    foreach ($original_fids as $original_fid) {
      if (!in_array($original_fid, $fids)) {
        file_delete($original_fid);
      }
    }

    // Exit if there is no fids.
    if (empty($fids)) {
      return;
    }

    /** @var \Drupal\file\FileInterface[] $files */
    $files = $this->entityTypeManager->getStorage('file')->loadMultiple($fids);
    foreach ($files as $file) {
      $source_uri = $file->getFileUri();
      $destination_uri = $this->getFileDestinationUri($element, $file, $webform_submission);

      // Save file if there is a new destination URI.
      if ($source_uri != $destination_uri) {
        $destination_uri = file_unmanaged_move($source_uri, $destination_uri);
        $file->setFileUri($destination_uri);
        $file->setFileName($this->fileSystem->basename($destination_uri));
        $file->save();
      }

      // Update file usage table.
      // Set file usage which will also make the file's status permanent.
      $this->fileUsage->delete($file, 'webform', 'webform_submission', $webform_submission->id(), 0);
      $this->fileUsage->add($file, 'webform', 'webform_submission', $webform_submission->id());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postDelete(array &$element, WebformSubmissionInterface $webform_submission) {
    // Delete File record.
    $fids = (array) ($webform_submission->getElementData($element['#webform_key']) ?: []);
    foreach ($fids as $fid) {
      file_delete($fid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    if ($this->isDisabled() || !isset($element['#webform_key'])) {
      return NULL;
    }

    $file_extensions = explode(' ', $this->getFileExtensions($element));
    $file_extension = $file_extensions[array_rand($file_extensions)];
    $upload_location = $this->getUploadLocation($element, $webform);
    $file_destination = $upload_location . '/' . $element['#webform_key'] . '.' . $file_extension;

    // Look for an existing temp files that have not been uploaded.
    $fids = $this->entityTypeManager->getStorage('file')->getQuery()
      ->condition('status', FALSE)
      ->condition('uid', $this->currentUser->id())
      ->condition('uri', $upload_location . '/' . $element['#webform_key'] . '.%', 'LIKE')
      ->execute();
    if ($fids) {
      return reset($fids);
    }

    // Copy sample file or generate a new temp file that can be uploaded.
    $sample_file = drupal_get_path('module', 'webform') . '/tests/files/sample.' . $file_extension;
    if (file_exists($sample_file)) {
      $file_uri = file_unmanaged_copy($sample_file, $file_destination);
    }
    else {
      $file_uri = file_unmanaged_save_data('{empty}', $file_destination);
    }

    $file = $this->entityTypeManager->getStorage('file')->create([
      'uri' => $file_uri,
      'uid' => $this->currentUser->id(),
    ]);
    $file->save();

    $fid = $file->id();
    return [$fid];
  }

  /**
   * Get max file size for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   Max file size.
   */
  protected function getMaxFileSize(array $element) {
    // Set max file size.
    $max_filesize = $this->configFactory->get('webform.settings')->get('file.default_max_filesize') ?: file_upload_max_size();
    $max_filesize = Bytes::toInt($max_filesize);
    if (!empty($element['#max_filesize'])) {
      $max_filesize = min($max_filesize, Bytes::toInt($element['#max_filesize']) * 1024 * 1024);
    }
    return $max_filesize;
  }

  /**
   * Get allowed file extensions for an element.
   *
   * @param array $element
   *   An element.
   *
   * @return int
   *   File extension.
   */
  protected function getFileExtensions(array $element = NULL) {
    if (!empty($element['#file_extensions'])) {
      return $element['#file_extensions'];
    }

    $file_type = str_replace('webform_', '', $this->getPluginId());
    return $this->configFactory->get('webform.settings')->get("file.default_{$file_type}_extensions");
  }

  /**
   * Get file upload location.
   *
   * @param array $element
   *   An element.
   * @param \Drupal\webform\WebformInterface $webform
   *   A webform.
   *
   * @return string
   *   Upload location.
   */
  protected function getUploadLocation(array $element, WebformInterface $webform) {
    if (empty($element['#upload_location'])) {
      $upload_location = $this->getUriScheme($element) . '://webform/' . $webform->id() . '/_sid_';
    }
    else {
      $upload_location = $element['#upload_location'];
    }

    // Make sure the upload location exists and is writable.
    file_prepare_directory($upload_location, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);

    return $upload_location;
  }

  /**
   * Get file upload URI scheme.
   *
   * Defaults to private file uploads.
   *
   * Drupal file upload by anonymous or untrusted users into public file systems
   * -- PSA-2016-003.
   *
   * @param array $element
   *   An element.
   *
   * @return string
   *   File upload URI scheme.
   *
   * @see https://www.drupal.org/psa-2016-003
   */
  protected function getUriScheme(array $element) {
    if (isset($element['#uri_scheme'])) {
      return $element['#uri_scheme'];
    }
    $scheme_options = static::getVisibleStreamWrappers();
    if (isset($scheme_options['private'])) {
      return 'private';
    }
    elseif (isset($scheme_options['public'])) {
      return 'public';
    }
    else {
      return 'private';
    }
  }

  /**
   * Form API callback. Consolidate the array of fids for this field into a single fids.
   */
  public static function validateManagedFile(array &$element, FormStateInterface $form_state, &$complete_form) {
    if (!empty($element['#files'])) {
      $fids = array_keys($element['#files']);
      if (empty($element['#multiple'])) {
        $form_state->setValueForElement($element, reset($fids));
      }
      else {
        $form_state->setValueForElement($element, $fids);
      }
    }
    else {
      $form_state->setValueForElement($element, NULL);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function validateMultiple(array &$element, FormStateInterface $form_state) {
    // Don't validate #multiple when a file is being removed.
    $trigger_element = $form_state->getTriggeringElement();
    if (end($trigger_element['#parents']) == 'remove_button') {
      return;
    }

    parent::validateMultiple($element, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['file'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File settings'),
    ];
    $scheme_options = static::getVisibleStreamWrappers();

    $form['file']['uri_scheme'] = [
      '#type' => 'radios',
      '#title' => t('Upload destination'),
      '#description' => t('Select where the final files should be stored. Private file storage has more overhead than public files, but allows restricted access to files within this element.'),
      '#required' => TRUE,
      '#options' => $scheme_options,
    ];
    // Public files security warning.
    if (isset($scheme_options['public'])) {
      $form['file']['uri_public_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Public files upload destination is dangerous for webforms that are available to anonymous and/or untrusted users.') . ' ' .
          $this->t('For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
        '#states' => [
          'visible' => [
            ':input[name="properties[uri_scheme]"]' => ['value' => 'public'],
          ],
        ],
      ];
    }
    // Private files not set warning.
    if (!isset($scheme_options['private'])) {
      $form['file']['uri_private_warning'] = [
        '#type' => 'webform_message',
        '#message_type' => 'warning',
        '#message_message' => $this->t('Private file system is not set. This must be changed in <a href="https://www.drupal.org/documentation/modules/file">settings.php</a>. For more information see: <a href="https://www.drupal.org/psa-2016-003">DRUPAL-PSA-2016-003</a>'),
        '#access' => TRUE,
      ];
    }

    $max_filesize = \Drupal::config('webform.settings')->get('file.default_max_filesize') ?: file_upload_max_size();
    $max_filesize = Bytes::toInt($max_filesize);
    $max_filesize = ($max_filesize / 1024 / 1024);
    $form['file']['max_filesize'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum file size'),
      '#field_suffix' => $this->t('MB (Max: @filesize MB)', ['@filesize' => $max_filesize]),
      '#placeholder' => $max_filesize,
      '#description' => $this->t('Enter the max file size a user may upload.'),
      '#min' => 1,
      '#max' => $max_filesize,
    ];
    $form['file']['file_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Allowed file extensions'),
      '#description' => $this->t('Separate extensions with a space and do not include the leading dot.'),
      '#maxlength' => 255,
    ];
    $form['file']['file_name'] = [
      '#type' => 'webform_checkbox_value',
      '#title' => $this->t('Rename files'),
      '#description' => $this->t('Rename uploaded files to this tokenized pattern. Do not include the extension here. The actual file extension will be automatically appended to this pattern.'),
      '#element' => [
        '#type' => 'textfield',
        '#title' => $this->t('File name pattern'),
        '#maxlength' => NULL,
      ],
    ];
    $form['file']['sanitize'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sanitize file name'),
      '#description' => $this->t('If checked, file name will be transliterated, lower-cased and all special characters converted to dashes (-).'),
      '#return_value' => TRUE,
    ];
    $t_args = [
      '%file_rename' => $form['file']['file_name']['#title'],
      '%sanitization' => $form['file']['sanitize']['#title'],
    ];
    $form['file']['file_name_warning'] = [
      '#type' => 'webform_message',
      '#message_type' => 'warning',
      '#message_message' => $this->t('For security reasons we advise to use %file_rename together with the %sanitization option.', $t_args),
      '#access' => TRUE,
      '#states' => ['visible' => [
        ':input[name="properties[file_name][checkbox]"]' => ['checked' => TRUE],
        ':input[name="properties[sanitize]"]' => ['checked' => FALSE],
      ]],
    ];
    $form['file']['multiple'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Multiple'),
      '#description' => $this->t('Check this option if the user should be allowed to upload multiple files.'),
      '#return_value' => TRUE,
    ];

    // Button.
    // @see webform_preprocess_file_managed_file()
    $form['file']['button'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Replace file upload input with an upload button'),
      '#description' => $this->t('If checked the file upload input will be replaced with click-able label styled as button.'),
      '#return_value' => TRUE,
    ];
    $form['file']['button__title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Upload button title'),
      '#description' => $this->t('Defaults to: %value', ['%value' => $this->t('Choose file')]),
      '#states' => [
        'visible' => [
          ':input[name="properties[button]"]' => ['checked' => TRUE],
        ],
      ],
    ];
    $form['file']['button__attributes'] = [
      '#type' => 'webform_element_attributes',
      '#title' => $this->t('Upload button attributes'),
      '#classes' => $this->configFactory->get('webform.settings')->get('settings.button_classes'),
      '#class__description' => $this->t("Apply classes to the button. Button classes default to 'button button-primary'."),
      '#states' => [
        'visible' => [
          ':input[name="properties[button]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Hide default value, which is not applicable for file uploads.
    $form['default']['#access'] = FALSE;

    return $form;
  }

  /**
   * Determine the destination URI where to save an uploaded file.
   *
   * @param array $element
   *   Element whose destination URI is requested.
   * @param \Drupal\file\FileInterface $file
   *   File whose destination URI is requested.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   Webform submission that contains the file whose destination URI is
   *   requested.
   *
   * @return string
   *   Destination URI under which the file should be saved.
   */
  protected function getFileDestinationUri(array $element, FileInterface $file, WebformSubmissionInterface $webform_submission) {
    $destination_folder = $this->fileSystem->dirname($file->getFileUri());
    $destination_filename = $file->getFilename();
    $destination_extension = pathinfo($destination_filename, PATHINFO_EXTENSION);

    // Replace /_sid_/ token with the submission id.
    if (strpos($destination_folder, '/_sid_')) {
      $destination_folder = str_replace('/_sid_', '/' . $webform_submission->id(), $destination_folder);
      file_prepare_directory($destination_folder, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS);
    }

    // Replace tokens in filename if we are instructed so.
    if (isset($element['#file_name']) && $element['#file_name']) {
      $destination_filename = $this->tokenManager->replace($element['#file_name'], $webform_submission) . '.' . $destination_extension;
    }

    // Sanitize filename.
    // @see http://stackoverflow.com/questions/2021624/string-sanitizer-for-filename
    if (!empty($element['#sanitize'])) {
      $destination_extension = Unicode::strtolower($destination_extension);

      $destination_basename = substr(pathinfo($destination_filename, PATHINFO_BASENAME), 0, -strlen(".$destination_extension"));
      $destination_basename = Unicode::strtolower($destination_basename);
      $destination_basename = $this->transliteration->transliterate($destination_basename, $this->languageManager->getCurrentLanguage()->getId(), '-');
      $destination_basename = preg_replace('([^\w\s\d\-_~,;:\[\]\(\].]|[\.]{2,})', '', $destination_basename);
      $destination_basename = preg_replace('/\s+/', '-', $destination_basename);
      $destination_basename = trim($destination_basename, '-');
      // If the basename if empty use the element's key.
      if (empty($destination_basename)) {
        $destination_basename = $element['#webform_key'];
      }

      $destination_filename = $destination_basename . '.' . $destination_extension;
    }

    return $destination_folder . '/' . $destination_filename;
  }

  /**
   * Control access to webform submission private file downloads.
   *
   * @param string $uri
   *   The URI of the file.
   *
   * @return mixed
   *   Returns NULL if the file is not attached to a webform submission.
   *   Returns -1 if the user does not have permission to access a webform.
   *   Returns an associative array of headers.
   *
   * @see hook_file_download()
   * @see webform_file_download()
   */
  public static function accessFileDownload($uri) {
    $files = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->loadByProperties(['uri' => $uri]);
    if (empty($files)) {
      return NULL;
    }

    /** @var \Drupal\file\FileInterface $file */
    $file = reset($files);
    if (empty($file)) {
      return NULL;
    }

    /** @var \Drupal\file\FileUsage\FileUsageInterface $file_usage */
    $file_usage = \Drupal::service('file.usage');
    $usage = $file_usage->listUsage($file);
    foreach ($usage as $module => $entity_types) {
      // Check for Webform module.
      if ($module != 'webform') {
        continue;
      }

      foreach ($entity_types as $entity_type => $counts) {
        $entity_ids = array_keys($counts);

        // Check for webform submission entity type.
        if ($entity_type != 'webform_submission' || empty($entity_ids)) {
          continue;
        }

        // Get webform submission.
        $webform_submission = WebformSubmission::load(reset($entity_ids));
        if (!$webform_submission) {
          continue;
        }

        // Check webform submission view access.
        if (!$webform_submission->access('view')) {
          return -1;
        }

        // Return file content headers.
        $headers = file_get_content_headers($file);

        // Force blacklisted files to be downloaded.
        if (in_array($headers['Content-Type'], static::$blacklistedMimeTypes)) {
          $headers['Content-Disposition'] = 'attachment';
        }

        return $headers;
      }
    }
    return NULL;
  }

  /**
   * Get visible stream wrappers.
   *
   * @return array
   *   An associative array of visible stream wrappers keyed by type.
   */
  public static function getVisibleStreamWrappers() {
    $stream_wrappers = \Drupal::service('stream_wrapper_manager')->getNames(StreamWrapperInterface::WRITE_VISIBLE);
    if (!\Drupal::config('webform.settings')->get('file.file_public')) {
      unset($stream_wrappers['public']);
    }
    return $stream_wrappers;
  }

}
