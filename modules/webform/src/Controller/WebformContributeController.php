<?php

namespace Drupal\webform\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides route responses for webform contribute.
 */
class WebformContributeController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The configuration object factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Constructs a WebfomrContributeController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * Returns webform contribute page.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return array
   *   A renderable array containing a webform about page.
   */
  public function index(Request $request) {
    /** @var \Drupal\webform\WebformContributeManagerInterface $contribute_manager */
    $contribute_manager = \Drupal::service('webform.contribute_manager');

    // Message.
    $build['message'] = [];
    $build['message']['divide'] = $this->buildDivider();
    $build['message']['quote'] = [
      '#markup' => $this->t('The question is not should you contribute, but how can you contribute'),
      '#prefix' => '<blockquote class="webform-contribute-quote">',
      '#suffix' => '</blockquote>',
    ];

    // Community Information.
    $build['community_info'] = [
      '#theme' => 'webform_contribute',
      '#account' => $contribute_manager->getAccount(),
      '#membership' => $contribute_manager->getMembership(),
      '#contribution' => $contribute_manager->getContribution(),
    ];

    // Drupal.
    $build['content']['#prefix'] = '<div class="webform-contribute-content">';
    $build['content']['#suffix'] = '</div>';
    $build['content']['drupal'] = [];
    $build['content']['drupal']['title'] = [
      '#markup' => $this->t('About Drupal'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    // Image.
    // @see https://pixabay.com/p-2009183
    $build['content']['drupal']['image'] = [
      '#theme' => 'image',
      '#uri' => drupal_get_path('module', 'webform') . '/images/contribute/contribute.png',
      '#alt' => $this->t('Webform: Contribute'),
      '#attributes' => [
        'class' => ['webform-contribute-image'],
      ],
    ];

    $build['content']['drupal']['content'] = [
      '#markup' => $this->t("The Drupal project is open source software. Anyone can download, use, work on, and share it with others. It's built on <a href=\"https://www.drupal.org/about/mission-and-principles\">principles</a> like collaboration, globalism, and innovation. It's distributed under the terms of the <a href=\"http://www.gnu.org/copyleft/gpl.html\">GNU General Public License</a> (GPL). There are <a href=\"https://www.drupal.org/about/licensing\">no licensing fees</a>, ever. Drupal will always be free."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['content']['drupal']['link'] = $this->buildLink(
      $this->t('Become a member of the Drupal community'),
      'https://register.drupal.org/user/register?destination=/project/webform'
    );
    $build['content']['drupal']['divider'] = $this->buildDivider();

    // Community.
    $build['content']['community'] = [];
    $build['content']['community']['title'] = [
      '#markup' => $this->t('The Drupal Community'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['content']['community']['image'] = [
      '#theme' => 'image',
      '#uri' => 'https://pbs.twimg.com/media/C-RXmp7XsAEgMN2.jpg',
      '#alt' => $this->t('DrupalCon Baltimore'),
      '#attributes' => [
        'class' => ['webform-contribute-image'],
      ],
    ];
    $build['content']['community']['quote'] = [
      '#prefix' => '<blockquote>',
      '#suffix' => '</blockquote>',
    ];
    $build['content']['community']['quote'][] = [
      '#markup' => $this->t("It’s really the Drupal community and not so much the software that makes the Drupal project what it is. So fostering the Drupal community is actually more important than just managing the code base."),
      '#prefix' => '<address>',
      '#suffix' => '</address>',
    ];
    $build['content']['community']['quote'][] = [
      '#markup' => $this->t('- Dries Buytaert'),
    ];

    $build['content']['community']['content'] = [
      '#markup' => $this->t("The Drupal community is one of the largest open source communities in the world. We're more than 1,000,000 passionate developers, designers, trainers, strategists, coordinators, editors, and sponsors working together. We build Drupal, provide support, create documentation, share networking opportunities, and more. Our shared commitment to the open source spirit pushes the Drupal project forward. New members are always welcome."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['content']['community']['link'] = $this->buildLink(
      $this->t('Get involved in the Drupal community'),
      'https://www.drupal.org/getting-involved'
    );
    $build['content']['community']['divide'] = $this->buildDivider();

    // Association.
    $build['content']['association'] = [];
    $build['content']['association']['title'] = [
      '#markup' => $this->t('Meet the Drupal Association'),
      '#prefix' => '<h2>',
      '#suffix' => '</h2>',
    ];
    $build['content']['association']['video'] = $this->buildVideo('LZWqFSMul84');
    $build['content']['association']['content'] = [
      '#markup' => $this->t("The Drupal Association is dedicated to fostering and supporting the Drupal software project, the community, and its growth. We help the Drupal community with funding, infrastructure, education, promotion, distribution, and online collaboration at Drupal.org."),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
    $build['content']['association']['link'] = $this->buildLink(
      $this->t('Learn more about the Drupal Association'),
      'https://www.drupal.org/association/campaign/value-2017?utm_source=webform&utm_medium=referral&utm_campaign=membership-webform-2017-11-06'
    );

    return $build;
  }

  /**
   * Returns account type autocomplete matches.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param string $account_type
   *   The account type to autocomplete.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   The JSON response.
   */
  public function autocomplete(Request $request, $account_type = 'user') {
    $q = $request->query->get('q');

    switch ($account_type) {
      case 'user':
        $response = $this->httpClient->get('https://www.drupal.org/index.php?q=admin/views/ajax/autocomplete/user/' . urlencode($q));
        $data = Json::decode($response->getBody());
        $matches = [];
        foreach ($data as $value) {
          $matches[] = ['value' => $value, 'label' => $value];
        }
        return new JsonResponse($matches);

      case 'organization':
        $response = $this->httpClient->get('https://www.drupal.org/index.php?q=entityreference/autocomplete/tags/field_for_customer/comment/comment_node_project_issue/NULL/' . urlencode($q));
        $data = Json::decode($response->getBody());
        $matches = [];
        foreach ($data as $value) {
          $value = strip_tags($value);
          $matches[] = ['value' => $value, 'label' => $value];
        }
        return new JsonResponse($matches);
    }
  }


  /****************************************************************************/
  // Build methods.
  /****************************************************************************/

  /**
   * Build a divider.
   *
   * @return array
   *   A render array containing an HR.
   */
  protected function buildDivider() {
    return ['#markup' => '<p><hr /></p>'];
  }

  /**
   * Build a link.
   *
   * @param string $title
   *   Link title.
   * @param string $url
   *   Link URL.
   * @param array $class
   *   Link class names.
   *
   * @return array
   *   A render array containing a link.
   */
  protected function buildLink($title, $url, array $class = ['button']) {
    if (is_string($url)) {
      $url = Url::fromUri($url);
    }
    return [
      '#type' => 'link',
      '#title' => $title . ' ›',
      '#url' => $url,
      '#attributes' => ['class' => $class],
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];
  }

  /**
   * Build about video player or linked button.
   *
   * @param string $youtube_id
   *   A YouTube id.
   *
   * @return array
   *   A video player, linked button, or an empty array if videos are disabled.
   */
  protected function buildVideo($youtube_id) {
    $video_display = $this->configFactory->get('webform.settings')->get('ui.video_display');
    switch ($video_display) {
      case 'dialog':
        return [
          '#theme' => 'webform_help_video_youtube',
          '#youtube_id' => $youtube_id,
          '#autoplay' => FALSE,
        ];

      case 'link':
        return [
          '#type' => 'link',
          '#title' => $this->t('Watch video'),
          '#url' => Url::fromUri('https://youtu.be/' . $youtube_id),
          '#attributes' => ['class' => ['button', 'button-action', 'button--small', 'button-webform-play']],
          '#prefix' => ' ',
        ];

      case 'hidden':
      default:
        return [];
    }
  }

}
