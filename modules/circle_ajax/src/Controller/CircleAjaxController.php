<?php 
namespace Drupal\circle_ajax\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Locale\CountryManager;

class CircleAjaxController extends ControllerBase {
  public function livecam() {
    $build = array(
      '#theme' => 'circle_ajax_livecam',
      '#imageUrl' => 'http://www.fadeout.ch/public/kloten-webcam.php?time=1&size=0&bust'.time(),
      '#cache' => array('max-age' => 0)
    );
    $response = new Response();
    $response->setContent(drupal_render($build));
    return $response;
  }
}