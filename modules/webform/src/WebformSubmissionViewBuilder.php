<?php

namespace Drupal\webform;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Render\Element;
use Drupal\webform\Plugin\WebformElementManagerInterface;
use Drupal\webform\Utility\WebformYaml;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Render controller for webform submissions.
 */
class WebformSubmissionViewBuilder extends EntityViewBuilder implements WebformSubmissionViewBuilderInterface {

  /**
   * Webform request handler.
   *
   * @var \Drupal\webform\WebformRequestInterface
   */
  protected $requestManager;

  /**
   * The webform element manager service.
   *
   * @var \Drupal\webform\Plugin\WebformElementManagerInterface
   */
  protected $elementManager;

  /**
   * The webform submission (server-side) conditions (#states) validator.
   *
   * @var \Drupal\webform\WebformSubmissionConditionsValidator
   */
  protected $conditionsValidator;

  /**
   * Constructs a WebformSubmissionViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\webform\WebformRequestInterface $webform_request
   *   The webform request handler.
   * @param \Drupal\webform\Plugin\WebformElementManagerInterface $element_manager
   *   The webform element manager service.
   * @param \Drupal\webform\WebformSubmissionConditionsValidatorInterface $conditions_validator
   *   The webform submission conditions (#states) validator.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, WebformRequestInterface $webform_request, WebformElementManagerInterface $element_manager, WebformSubmissionConditionsValidatorInterface $conditions_validator) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->requestManager = $webform_request;
    $this->elementManager = $element_manager;
    $this->conditionsValidator = $conditions_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('webform.request'),
      $container->get('plugin.manager.webform.element'),
      $container->get('webform_submission.conditions_validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    /** @var \Drupal\webform\WebformSubmissionInterface[] $entities */
    foreach ($entities as $id => $webform_submission) {
      $webform = $webform_submission->getWebform();

      if ($view_mode === 'preview') {
        $options = [
          'excluded_elements' => $webform->getSetting('preview_excluded_elements'),
          'exclude_empty' => $webform->getSetting('preview_exclude_empty'),
        ];
      }
      else {
        $options = [
          'excluded_elements' => $webform->getSetting('excluded_elements'),
          'exclude_empty' => $webform->getSetting('exclude_empty'),
        ];
      }

      switch ($view_mode) {
        case 'yaml':
          $data = $webform_submission->toArray(TRUE, TRUE);
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => WebformYaml::tidy(Yaml::encode($data)),
            '#type' => 'yaml',
          ];
          break;

        case 'text':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = [
            '#theme' => 'webform_codemirror',
            '#code' => $this->buildElements($elements, $webform_submission, $options, 'text'),
          ];
          break;

        case 'table':
          $elements = $webform->getElementsInitializedFlattenedAndHasValue();
          $build[$id]['data'] = $this->buildTable($elements, $webform_submission, $options);
          break;

        default:
        case 'html':
          $elements = $webform->getElementsInitialized();
          $build[$id]['data'] = $this->buildElements($elements, $webform_submission, $options);
          break;
      }
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);
  }

  /**
   * {@inheritdoc}
   */
  public function buildElements(array $elements, WebformSubmissionInterface $webform_submission, array $options = [], $format = 'html') {
    $build_method = 'build' . ucfirst($format);
    $build = [];

    foreach ($elements as $key => $element) {
      // Make sure this is an element.
      if (!is_array($element) || Element::property($key)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      if ($build_element = $webform_element->$build_method($element, $webform_submission, $options)) {
        $build[$key] = $build_element;
        if (!$this->isElementVisible($element, $webform_submission, $options)) {
          $build[$key]['#access'] = FALSE;
        };
      }
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTable(array $elements, WebformSubmissionInterface $webform_submission, array $options = []) {
    $rows = [];
    foreach ($elements as $key => $element) {
      if (!$this->isElementVisible($element, $webform_submission, $options)) {
        continue;
      }

      /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
      $webform_element = $this->elementManager->getElementInstance($element);

      // Replace tokens before building the element.
      $webform_element->replaceTokens($element, $webform_submission);

      $title = $element['#admin_title'] ?: $element['#title'] ?: '(' . $key . ')';
      $html = $webform_element->formatHtml($element, $webform_submission, $options);
      $rows[] = [
        ['header' => TRUE, 'data' => $title],
        ['data' => (is_string($html)) ? ['#markup' => $html] : $html],
      ];
    }

    return [
      '#type' => 'table',
      '#rows' => $rows,
      '#attributes' => [
        'class' => ['webform-submission__table'],
      ],
    ];
  }

  /**
   * Determines if an element is visible.
   *
   * @param array $element
   *   The element to check for visibility.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   A webform submission.
   * @param array $options
   *   - excluded_elements: An array of elements to be excluded.
   *   - ignore_access: Flag to ignore private and/or access controls and always
   *     display the element.
   *   - email: Format element to be send via email.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   *
   * @see \Drupal\webform\WebformSubmissionConditionsValidatorInterface::isElementVisible
   * @see \Drupal\Core\Render\Element::isVisibleElement
   *
   */
  protected function isElementVisible(array $element, WebformSubmissionInterface $webform_submission, array $options) {
    // Checked excluded elements.
    if (isset($element['#webform_key']) && isset($options['excluded_elements'][$element['#webform_key']])) {
      return FALSE;
    }

    // Check if the element is conditionally hidden.
    if (!$this->conditionsValidator->isElementVisible($element, $webform_submission)) {
      return FALSE;
    }

    // Check if ignore access is set.
    // This is used email handlers to include administrative elements in emails.
    if (!empty($options['ignore_access'])) {
      return TRUE;
    }

    // Check check the element's #access.
    if (isset($element['#access']) && (($element['#access'] instanceof AccessResultInterface && $element['#access']->isForbidden()) || ($element['#access'] === FALSE))) {
      return FALSE;
    }

    // Finally, check the element's 'view' access.
    /** @var \Drupal\webform\Plugin\WebformElementInterface $webform_element */
    $webform_element = $this->elementManager->getElementInstance($element);
    return $webform_element->checkAccessRules('view', $element) ? TRUE : FALSE;
  }

}
