<?php

namespace Drupal\webform;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Utility\WebformDialogHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Defines a class to build a listing of webform entities.
 *
 * @see \Drupal\webform\Entity\Webform
 */
class WebformEntityListBuilder extends ConfigEntityListBuilder {

  /**
   * Search keys.
   *
   * @var string
   */
  protected $keys;

  /**
   * Search category.
   *
   * @var string
   */
  protected $category;

  /**
   * Search state.
   *
   * @var string
   */
  protected $state;

  /**
   * Webform submission storage.
   *
   * @var \Drupal\webform\WebformSubmissionStorageInterface
   */
  protected $submissionStorage;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * Role storage.
   *
   * @var \Drupal\user\RoleStorageInterface
   */
  protected $roleStorage;

  /**
   * Associative array container total results for all webforms.
   *
   * @var array
   */
  protected $resultsTotals;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    parent::__construct($entity_type, $storage);

    $this->keys = \Drupal::request()->query->get('search');
    $this->category = \Drupal::request()->query->get('category');
    $this->state = \Drupal::request()->query->get('state');
    $this->submissionStorage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $this->userStorage = \Drupal::entityTypeManager()->getStorage('user');
    $this->roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    // Handler autocomplete redirect.
    if ($this->keys && preg_match('#\(([^)]+)\)$#', $this->keys, $match)) {
      if ($webform = $this->getStorage()->load($match[1])) {
        return new RedirectResponse($webform->toUrl()->setAbsolute(TRUE)->toString());
      }
    }

    $build = [];

    // Must manually add local actions to the webform because we can't alter local
    // actions and add the needed dialog attributes.
    // @see https://www.drupal.org/node/2585169
    if (\Drupal::currentUser()->hasPermission('create webform')) {
      $build['local_actions'] = [
        'add_form' => [
          '#type' => 'link',
          '#title' => $this->t('Add webform'),
          '#url' => new Url('entity.webform.add_form'),
          '#attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW, ['button', 'button-action', 'button--primary', 'button--small']),
        ],
      ];
    }

    // Add the filter by key(word) and/or state.
    if (\Drupal::currentUser()->hasPermission('administer webform')) {
      $state_options = [
        '' => $this->t('All [@total]', ['@total' => $this->getTotal(NULL, NULL)]),
        WebformInterface::STATUS_OPEN => $this->t('Open [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_OPEN)]),
        WebformInterface::STATUS_CLOSED => $this->t('Closed [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_CLOSED)]),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled [@total]', ['@total' => $this->getTotal(NULL, NULL, WebformInterface::STATUS_SCHEDULED)]),
      ];
    }
    else {
      $state_options = [
        '' => $this->t('All'),
        WebformInterface::STATUS_OPEN => $this->t('Open'),
        WebformInterface::STATUS_CLOSED => $this->t('Closed'),
        WebformInterface::STATUS_SCHEDULED => $this->t('Scheduled'),
      ];
    }
    $build['filter_form'] = \Drupal::formBuilder()->getForm('\Drupal\webform\Form\WebformEntityFilterForm', $this->keys, $this->category, $this->state, $state_options);

    // Display info.
    if (\Drupal::currentUser()->hasPermission('administer webform') && ($total = $this->getTotal($this->keys, $this->category, $this->state))) {
      $build['info'] = [
        '#markup' => $this->formatPlural($total, '@total webform', '@total webforms', ['@total' => $total]),
        '#prefix' => '<div>',
        '#suffix' => '</div>',
      ];
    }

    $build += parent::render();

    $build['table']['#attributes']['class'][] = 'webform-forms';

    // Must preload libraries required by (modal) dialogs.
    WebformDialogHelper::attachLibraries($build);

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['title'] = [
      'data' => $this->t('Title'),
      'specifier' => 'title',
      'field' => 'title',
      'sort' => 'asc',
    ];
    $header['description'] = [
      'data' => $this->t('Description'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'description',
      'field' => 'description',
    ];
    $header['category'] = [
      'data' => $this->t('Category'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'category',
      'field' => 'category',
    ];
    $header['status'] = [
      'data' => $this->t('Status'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'status',
      'field' => 'status',
    ];
    $header['author'] = [
      'data' => $this->t('Author'),
      'class' => [RESPONSIVE_PRIORITY_LOW],
      'specifier' => 'uid',
      'field' => 'uid',
    ];
    $header['results_total'] = [
      'data' => $this->t('Total Results'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      'specifier' => 'results_total',
      'field' => 'results_total',
    ];
    $header['results_operations'] = [
      'data' => $this->t('Operations'),
      'class' => [RESPONSIVE_PRIORITY_MEDIUM],
    ];
    $header['operations'] = '';

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\webform\WebformInterface */
    $settings = $entity->getSettings();

    // ISSUE: Webforms that the current user can't access are not being hidden via the EntityQuery.
    // WORK-AROUND: Don't link to the webform.
    // See: Access control is not applied to config entity queries
    // https://www.drupal.org/node/2636066
    $row['title']['data']['title'] = ['#markup' => ($entity->access('submission_page')) ? $entity->toLink()->toString() : $entity->label()];
    if ($entity->isTemplate()) {
      $row['title']['data']['template'] = ['#markup' => ' <b>(' . $this->t('Template') . ')</b>'];
    }
    $row['description']['data'] = WebformHtmlEditor::checkMarkup($entity->get('description'));
    $row['category']['data']['#markup'] = $entity->get('category');
    switch ($entity->get('status')) {
      case WebformInterface::STATUS_OPEN:
        $row['status'] = $this->t('Open');
        break;

      case WebformInterface::STATUS_CLOSED:
        $row['status'] = $this->t('Closed');
        break;

      case WebformInterface::STATUS_SCHEDULED:
        $row['status'] = $this->t('Scheduled (@state)', ['@state' => $entity->isOpen() ? $this->t('Open') : $this->t('Closed')]);
        break;
    }
    $row['owner'] = ($owner = $entity->getOwner()) ? $owner->toLink() : '';
    $row['results_total'] = $this->getResultsTotal($entity->id()) . ($entity->isResultsDisabled() ? ' ' . $this->t('(Disabled)') : '');
    $row['results_operations']['data'] = [
      '#type' => 'operations',
      '#links' => $this->getDefaultOperations($entity, 'results'),
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperations(EntityInterface $entity) {
    return parent::buildOperations($entity) + [
      '#prefix' => '<div class="webform-dropbutton">',
      '#suffix' => '</div>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity, $type = 'edit') {
    /* @var $entity \Drupal\webform\WebformInterface */
    $route_parameters = ['webform' => $entity->id()];
    if ($type == 'results') {
      $operations = [];
      if ($entity->access('submission_view_any')) {
        $operations['submissions'] = [
          'title' => $this->t('Submissions'),
          'url' => Url::fromRoute('entity.webform.results_submissions', $route_parameters),
        ];
        $operations['export'] = [
          'title' => $this->t('Download'),
          'url' => Url::fromRoute('entity.webform.results_export', $route_parameters),
        ];
      }
      if ($entity->access('submission_delete_any')) {
        $operations['clear'] = [
          'title' => $this->t('Clear'),
          'url' => Url::fromRoute('entity.webform.results_clear', $route_parameters),
        ];
      }
    }
    else {
      $operations = parent::getDefaultOperations($entity);
      if (isset($operations['edit'])) {
        $operations['edit']['title'] = $this->t('Build');
      }
      if ($entity->access('update')) {
        $operations['settings'] = [
          'title' => $this->t('Settings'),
          'weight' => 22,
          'url' => Url::fromRoute('entity.webform.settings', $route_parameters),
        ];
      }
      if ($entity->access('submission_page')) {
        $operations['view'] = [
          'title' => $this->t('View'),
          'weight' => 24,
          'url' => Url::fromRoute('entity.webform.canonical', $route_parameters),
        ];
      }
      if ($entity->access('submission_update_any')) {
        $operations['test'] = [
          'title' => $this->t('Test'),
          'weight' => 25,
          'url' => Url::fromRoute('entity.webform.test_form', $route_parameters),
        ];
      }
      if ($entity->access('duplicate')) {
        $operations['duplicate'] = [
          'title' => $this->t('Duplicate'),
          'weight' => 26,
          'url' => Url::fromRoute('entity.webform.duplicate_form', $route_parameters),
          'attributes' => WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW),
        ];
      }
      if (isset($operations['delete'])) {
        $operations['delete']['attributes'] = WebformDialogHelper::getModalDialogAttributes(WebformDialogHelper::DIALOG_NARROW);
      }
    }
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $header = $this->buildHeader();
    if (\Drupal::request()->query->get('order') === (string) $header['results_total']['data']) {
      // Get results totals for all returned entity ids.
      $results_totals = $this->getQuery($this->keys, $this->category, $this->state)
        ->execute();
      foreach ($results_totals as $entity_id) {
        $results_totals[$entity_id] = $this->getResultsTotal($entity_id);
      }

      // Sort results totals.
      asort($results_totals, SORT_NUMERIC);
      if (\Drupal::request()->query->get('sort') === 'desc') {
        $results_totals = array_reverse($results_totals, TRUE);
      }

      // Build an associative array of entity ids.
      $entity_ids = array_keys($results_totals);
      $entity_ids = array_combine($entity_ids, $entity_ids);

      // Manually initialize and apply paging to the entity ids.
      $page = \Drupal::request()->query->get('page') ?: 0;
      $total = count($entity_ids);
      $limit = $this->getLimit();
      $start = ($page * $limit);
      pager_default_initialize($total, $limit);
      return array_slice($entity_ids, $start, $limit, TRUE);
    }
    else {
      $query = $this->getQuery($this->keys, $this->category, $this->state);
      $query->tableSort($header);
      $query->pager($this->getLimit());
      return $query->execute();
    }
  }

  /**
   * Get total number of results for a webform.
   *
   * @param string $webform_id
   *   A webform id.
   *
   * @return int
   *   Total number of results for a webform.
   */
  protected function getResultsTotal($webform_id) {
    $results_totals = $this->getResultsTotals();
    return (isset($results_totals[$webform_id])) ? $results_totals[$webform_id] : 0;
  }

  /**
   * Get total results for all webforms.
   *
   * @return array
   *   An associative array keyed by webform id contains total results for
   *   all webforms.
   */
  protected function getResultsTotals() {
    if (isset($this->resultsTotals)) {
      return $this->resultsTotals;
    }

    $query = \Drupal::database()->select('webform_submission', 'ws');
    $query->fields('ws', ['webform_id']);
    $query->addExpression('COUNT(sid)', 'results_total');
    $query->groupBy('webform_id');

    $this->resultsTotals = array_map('intval', $query->execute()->fetchAllKeyed());
    return $this->resultsTotals;
  }

  /**
   * Get the total number of submissions.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   * @param string $state
   *   (optional) Webform state. Can be 'open' or 'closed'.
   *
   * @return int
   *   The total number of submissions.
   */
  protected function getTotal($keys = '', $category = '', $state = '') {
    return $this->getQuery($keys, $category, $state)
      ->count()
      ->execute();
  }

  /**
   * Get the base entity query filtered by webform and search.
   *
   * @param string $keys
   *   (optional) Search key.
   * @param string $category
   *   (optional) Category.
   * @param string $state
   *   (optional) Webform state. Can be 'open' or 'closed'.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   An entity query.
   */
  protected function getQuery($keys = '', $category = '', $state = '') {
    $query = $this->getStorage()->getQuery();

    // Filter by key(word).
    if ($keys) {
      $or = $query->orConditionGroup()
        ->condition('id', $keys, 'CONTAINS')
        ->condition('title', $keys, 'CONTAINS')
        ->condition('description', $keys, 'CONTAINS')
        ->condition('category', $keys, 'CONTAINS')
        ->condition('elements', $keys, 'CONTAINS');

      // Users and roles we need to scan all webforms.
      $access_value = NULL;
      if ($accounts = $this->userStorage->loadByProperties(['name' => $keys])) {
        $account = reset($accounts);
        $access_type = 'users';
        $access_value = $account->id();
      }
      elseif ($role = $this->roleStorage->load($keys)) {
        $access_type = 'roles';
        $access_value = $role->id();
      }
      if ($access_value) {
        // Collect the webform ids that the user or role has access to.
        $webform_ids = [];
        /** @var \Drupal\webform\WebformInterface $webforms */
        $webforms = $this->getStorage()->loadMultiple();
        foreach ($webforms as $webform) {
          $access_rules = $webform->getAccessRules();
          foreach ($access_rules as $access_rule) {
            if (!empty($access_rule[$access_type]) && in_array($access_value, $access_rule[$access_type])) {
              $webform_ids[] = $webform->id();
              break;
            }
          }
        }
        if ($webform_ids) {
          $or->condition('id', $webform_ids, 'IN');
        }
        // Also check the webform's owner.
        if ($access_type == 'users') {
          $or->condition('uid', $access_value);
        }
      }
      $query->condition($or);
    }

    // Filter by category.
    if ($category) {
      $query->condition('category', $category);
    }

    // Filter by (form) state.
    if ($state) {
      $query->condition('status', $state);
    }

    // Filter out templates if the webform_template.module is enabled.
    if ($this->moduleHandler()->moduleExists('webform_templates')) {
      $query->condition('template', FALSE);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_ids = $this->getEntityIds();
    /* @var $entities \Drupal\webform\WebformInterface[] */
    $entities = $this->storage->loadMultiple($entity_ids);

    // If the user is not a webform admin, check access to each webform.
    if (!$this->isAdmin()) {
      foreach ($entities as $entity_id => $entity) {
        if (!$entity->access('update') && !$entity->access('submission_view_any')) {
          unset($entities[$entity_id]);
        }
      }
    }

    return $entities;
  }

  /**
   * Get number of entities to list per page.
   *
   * @return int|false
   *   The number of entities to list per page, or FALSE to list all entities.
   */
  protected function getLimit() {
    return ($this->isAdmin()) ? $this->limit : FALSE;
  }

  /**
   * Is the current user a webform administrator.
   *
   * @return bool
   *   TRUE if the current user has 'administer webform' or 'edit any webform'
   *   permission.
   */
  protected function isAdmin() {
    $account = \Drupal::currentUser();
    return ($account->hasPermission('administer webform') || $account->hasPermission('edit any webform') || $account->hasPermission('view any webform submission'));
  }

  /**
   * {@inheritdoc}
   */
  protected function ensureDestination(Url $url) {
    return $url;
  }

}
