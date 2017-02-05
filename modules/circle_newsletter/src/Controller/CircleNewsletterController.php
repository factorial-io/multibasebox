<?php 
namespace Drupal\circle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Locale\CountryManager;

class CircleNewsletterController extends ControllerBase {
  public function register($email) {
    $build = array(
      '#theme' => 'circle_newsletter_register',
      '#email' => UrlHelper::stripDangerousProtocols($email),
      '#countries' => CountryManager::getStandardList()
    );
    $response = new Response();
    $response->setContent(drupal_render($build));
    return $response;
  }
}
