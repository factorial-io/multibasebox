<?php 
namespace Drupal\circle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use \Drupal\Component\Utility\UrlHelper;

class CircleNewsletterController extends ControllerBase {
  public function register($email) {
    $build = array(
      '#theme' => 'circle_newsletter_register',
      '#email' => UrlHelper::stripDangerousProtocols($email)
    );
    $response = new Response();
    $response->setContent(drupal_render($build));
    return $response;
  }
}
