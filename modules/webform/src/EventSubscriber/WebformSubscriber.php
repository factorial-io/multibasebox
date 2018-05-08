<?php

namespace Drupal\webform\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\webform\Element\WebformHtmlEditor;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\Response;

/**
 * Event subscriber to redirect to login form when webform settings instruct to.
 */
class WebformSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The redirect.destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The webform token manager.
   *
   * @var \Drupal\webform\WebformTokenManagerInterface
   */
  protected $tokenManager;

  /**
   * Constructs a new WebformSubscriber.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect.destination service.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(AccountInterface $account, ConfigFactoryInterface $config_factory, RendererInterface $renderer, RedirectDestinationInterface $redirect_destination, WebformTokenManagerInterface $token_manager) {
    $this->account = $account;
    $this->configFactory = $config_factory;
    $this->renderer = $renderer;
    $this->redirectDestination = $redirect_destination;

    $this->tokenManager = $token_manager;
  }

  /**
   * Redirect to user login when access is denied to private webform file.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   *
   * @see webform_file_download()
   * @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::accessFileDownload
   */
  public function onRespondRedirectPrivateFileAccess(FilterResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Check for 403 access denied status code in (master) response.
    $response = $event->getResponse();
    if ($response->getStatusCode() != Response::HTTP_FORBIDDEN) {
      return;
    }

    $path = $event->getRequest()->getPathInfo();
    // Make sure the user is trying to access a private webform file upload.
    if (strpos($path, '/system/files/webform/') !== 0) {
      return;
    }

    // Make private webform file upload is not a temporary file.
    // @see \Drupal\webform\Plugin\WebformElement\WebformManagedFileBase::postSave
    if (strpos($path, '/_sid_/') !== FALSE) {
      return;
    }

    // Only redirect anonymous users.
    if ($this->account->isAuthenticated()) {
      return;
    }

    // Check that private file redirection is enabled.
    if (!$this->configFactory->get('webform.settings')->get('file.file_private_redirect')) {
      return;
    }

    $message = $this->configFactory->get('webform.settings')->get('file.file_private_redirect_message');
    $this->redirectToLogin($event, $message);
  }

  /**
   * Redirect to user login when access is denied for webform or submission.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   */
  public function onRespondRedirectEntityAccess(FilterResponseEvent $event) {
    if ($event->getRequestType() != HttpKernelInterface::MASTER_REQUEST) {
      return;
    }

    // Check for 403 access denied status code in (master) response.
    $response = $event->getResponse();
    if ($response->getStatusCode() != Response::HTTP_FORBIDDEN) {
      return;
    }

    $url = Url::fromUserInput($event->getRequest()->getPathInfo());
    if (!$url) {
      return;
    }

    $route_parameters = $url->isRouted() ? $url->getRouteParameters() : [];
    if (empty($route_parameters['webform']) && empty($route_parameters['webform_submission'])) {
      return;
    }

    // If webform submission, handle login redirect.
    if (!empty($route_parameters['webform_submission'])) {
      $webform_submission = WebformSubmission::load($route_parameters['webform_submission']);
      if ($webform_submission->getWebform()->getSetting('submission_login')) {
        $message = $webform_submission->getWebform()->getSetting('submission_login_message')
          ?: $this->configFactory->get('webform.settings')->get('settings.default_submission_login_message');
        $this->redirectToLogin($event, $message, $webform_submission);
      };
      return;
    }

    // If webform, handle login redirect.
    if (!empty($route_parameters['webform'])) {
      $webform = Webform::load($route_parameters['webform']);
      if ($webform->getSetting('form_login')) {
        $message = $webform->getSetting('form_login_message')
          ?: $this->configFactory->get('webform.settings')->get('settings.default_form_login_message');
        $this->redirectToLogin($event, $message, $webform);
      };
      return;
    }
  }

  /**
   * Redirect to user login with destination and display custom message.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   The event to process.
   * @param null|string $message
   *   (Optional) Message to be display on user login.
   * @param null|\Drupal\Core\Entity\EntityInterface $entity
   *   (Optional) Entity to be used when replacing tokens.
   */
  protected function redirectToLogin(FilterResponseEvent $event, $message = NULL, EntityInterface $entity = NULL) {
    // Display message.
    if ($message) {
      $message = $this->tokenManager->replace($message, $entity);
      $build = WebformHtmlEditor::checkMarkup($message);
      drupal_set_message($this->renderer->renderPlain($build));
    }

    $redirect_url = Url::fromRoute(
      'user.login',
      [],
      ['absolute' => TRUE, 'query' => $this->redirectDestination->getAsArray()]
    );
    $event->setResponse(new RedirectResponse($redirect_url->toString()));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[KernelEvents::RESPONSE][] = ['onRespondRedirectPrivateFileAccess'];
    $events[KernelEvents::RESPONSE][] = ['onRespondRedirectEntityAccess'];
    return $events;
  }

}
