<?php

namespace Drupal\webform\Form;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\EventSubscriber\MainContentViewSubscriber;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Drupal\webform\WebformContributeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure webform contribute settings for this site.
 */
class WebformContributeForm extends ConfigFormBase {

  /**
   * The render cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $renderCache;

  /**
   * The router builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routerBuilder;

  /**
   * The contribute manager.
   *
   * @var \Drupal\webform\WebformContributeManagerInterface
   */
  protected $contributeManager;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_contribute_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['webform.settings'];
  }

  /**
   * Constructs a ContributeSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheBackendInterface $render_cache
   *   The render cache service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $router_builder
   *   The router builder service.
   * @param \Drupal\webform\WebformContributeManagerInterface $contribute_manager
   *   The contribute manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $render_cache, RouteBuilderInterface $router_builder, WebformContributeManagerInterface $contribute_manager) {
    parent::__construct($config_factory);
    $this->renderCache = $render_cache;
    $this->routerBuilder = $router_builder;
    $this->contributeManager = $contribute_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache.render'),
      $container->get('router.builder'),
      $container->get('webform.contribute_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Add the core AJAX library.
    $form['#attached']['library'][] = 'core/drupal.ajax';

    $config = $this->config('webform.settings');
    $form['#attributes'] = ['novalidate' => TRUE];
    $form['account'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Drupal.org Account'),
      '#states' => [
        'visible' => [
          ':input[name="contribute_disabled"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['account']['account_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Account type'),
      '#description' => $this->t('Please select the type of Drupal.org account that you use to contribute back to Drupal'),
      '#options' => [
        'user' => $this->t('Individual user'),
        'organization' => $this->t('Organization'),
      ],
      '#default_value' => $config->get('contribute.account_type') ?: 'user',
      '#states' => [
        'required' => [
          ':input[name="contribute_disabled"]' => ['checked' => FALSE],
        ],
      ],
    ];
    $form['account']['user_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal.org user name'),
      '#description' => $this->t('Please enter your user name. <a href=":href">Create new user account</a>', [':href' => 'https://register.drupal.org/user/register']),
      '#default_value' => ($config->get('contribute.account_type') === 'user') ? $config->get('contribute.account_id') : '',
      '#autocomplete_route_name' => 'webform.contribute.autocomplete',
      '#autocomplete_route_parameters' => ['account_type' => 'user'],
      '#states' => [
        'required' => [
          ':input[name="account_type"]' => ['value' => 'user'],
        ],
        'visible' => [
          ':input[name="account_type"]' => ['value' => 'user'],
        ],
      ],
    ];
    $form['account']['organization_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Drupal.org organization name'),
      '#description' => $this->t('Please enter your organization\'s name. <a href=":href">Create new organization</a>', [':href' => 'https://www.drupal.org/node/add/organization']),
      '#default_value' => ($config->get('contribute.account_type') === 'organization') ? $config->get('contribute.account_id') : '',
      '#autocomplete_route_name' => 'webform.contribute.autocomplete',
      '#autocomplete_route_parameters' => ['account_type' => 'organization'],
      '#states' => [
        'required' => [
          ':input[name="account_type"]' => ['value' => 'organization'],
        ],
        'visible' => [
          ':input[name="account_type"]' => ['value' => 'organization'],
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['actions']['delete'] = [
      '#type' => 'submit',
      '#value' => $this->t('Clear'),
      '#attributes' => [
        'class' => ['button--danger'],
      ],
    ];

    // Add Ajax wrapper and submit to the form.
    if ($this->isModal()) {
      $form['#form_wrapper_id'] = $this->getWrapperId();
      $form['#prefix'] = '<div id="' . $this->getWrapperId() . '">';
      $form['#suffix'] = '</div>';
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::submitAjaxForm',
        'event' => 'click',
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if ((string) $form_state->getValue('op') === (string) $this->t('Clear') || $form_state->getValue('contribute_disabled')) {
      return;
    }

    $account_type = $form_state->getValue('account_type');
    $account_id = $form_state->getValue($account_type . '_id');

    $this->contributeManager->setAccountType($account_type);
    $this->contributeManager->setAccountId($account_id);

    $account = $this->contributeManager->getAccount();
    if (!$account['status']) {
      $t_args = [
        '@name' => ($account_type === 'individual') ? $this->t('Drupal.org user name') : $this->t('Drupal.org organization name'),
      ];
      $form_state->setErrorByName($account_type . '_id', $this->t('Invalid @name.', $t_args));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ((string) $form_state->getValue('op') === (string) $this->t('Clear')) {
      $account_type = NULL;
      $account_id = NULL;
      drupal_set_message($this->t('Community information has been cleared.'));
    }
    else {
      $account_type = $form_state->getValue('account_type');
      $account_id = $form_state->getValue($account_type . '_id');
      drupal_set_message($this->t('Your community information has been saved.'));
    }

    // Always clear cached information.
    Cache::invalidateTags(['webform_contribute']);

    $this->config('webform.settings')
      ->set('contribute.account_type', $account_type)
      ->set('contribute.account_id', $account_id)
      ->save();

    $form_state->setRedirect('webform.contribute');
  }

  /**
   * Submit form #ajax callback.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   An Ajax response that display validation error messages or redirects
   *   to a URL
   */
  public function submitAjaxForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      // Display messages first by prefixing it the form and setting its weight
      // to -1000.
      $form = [
        'status_messages' => [
          '#type' => 'status_messages',
          '#weight' => -1000,
        ],
      ] + $form;

      // Remove wrapper.
      unset($form['#prefix'], $form['#suffix']);

      $response = new AjaxResponse();
      $response->addCommand(new HtmlCommand('#' . $this->getWrapperId(), $form));
      return $response;
    }
    else {
      $response = new AjaxResponse();
      $response->addCommand(new RedirectCommand(Url::fromRoute('webform.contribute')->toString()));
      return $response;
    }
  }

  /**
   * Determine if this form is being displayed in a modal dialog.
   *
   * @return bool
   *   TRUE is the form is being display in a modal dialog.
   */
  protected function isModal() {
    $wrapper_format = $this->getRequest()->get(MainContentViewSubscriber::WRAPPER_FORMAT);
    return (in_array($wrapper_format, ['drupal_modal', 'drupal_ajax']));
  }

  /**
   * Get the form's Ajax wrapper id.
   *
   * @return string
   *   The form's Ajax wrapper id.
   */
  protected function getWrapperId() {
    return $this->getFormId() . '-ajax';
  }

}
