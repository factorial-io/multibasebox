<?php

namespace Drupal\webform;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;

/**
 * Class ContributeManager.
 */
class WebformContributeManager implements WebformContributeManagerInterface {

  use StringTranslationTrait;

  /**
   * The account type.
   *
   * @var string
   */
  protected $accountType;

  /**
   * The account id.
   *
   * @var string
   */
  protected $accountId;

  /**
   * The default cache bin.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache responses from HTTP get requests.
   *
   * @var array
   */
  protected $cachedData = [];

  /**
   * Cache people.
   *
   * @var array
   */
  protected $cachedPeople = [];

  /**
   * Constructs a new ContributeManager object.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The default cache bin.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The Guzzle HTTP client.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(CacheBackendInterface $cache, ClientInterface $http_client, DateFormatterInterface $date_formatter, ConfigFactoryInterface $config_factory) {
    $this->cache = $cache;
    $this->httpClient = $http_client;
    $this->dateFormatter = $date_formatter;
    $this->configFactory = $config_factory;

    $this->accountType = $this->configFactory->get('webform.settings')->get('contribute.account_type');
    $this->accountId = $this->configFactory->get('webform.settings')->get('contribute.account_id');
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    $account_type = $this->getAccountType();
    $account_id = $this->getAccountId() ?: 'anonymous';

    $cid = 'webform.contribute.account.' . md5("$account_type.$account_id");
    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $account = [];
    $account['status'] = FALSE;

    if ($account_id != 'anonymous') {
      switch ($account_type) {
        case 'organization':
          $organization = $this->getOrganization();
          if ($organization) {
            $account['status'] = TRUE;
            $account['url'] = Url::fromUri($organization['url']);
            $account['name'] = $organization['title'];
            $account['created'] = $organization['created'];
            $account['image'] = $this->getOrganizationLogo();
          }
          break;

        case 'user';
          $user = $this->getUser();
          if ($user) {
            $account['status'] = TRUE;
            $account['url'] = Url::fromUri($user['url']);
            if ($user['field_first_name'] || $user['field_last_name']) {
              $account['name'] = $user['field_first_name'] .
                ($user['field_first_name'] && $user['field_last_name'] ? ' ' : '') .
                $user['field_last_name'];
            }
            else {
              $account['name'] = $user['name'];
            }
            $account['created'] = $user['created'];
            $account['image'] = $this->getUserPicture();
            $account['organizations'] = [];
            foreach ($user['field_organizations'] as $item) {
              $data = $this->get($item['uri'] . '.json');
              if (!empty($data['field_organization_name'])) {
                $account['organizations'][] = $data['field_organization_name'];
              }
            }
          }
          break;
      }
    }

    $configure_attributes = [
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 600,
      ]),
    ];
    if (isset($account['name'])) {
      $account['value'] = [];
      $account['value']['name'] = [
        '#type' => 'link',
        '#title' => $account['name'],
        '#url' => $account['url'],
        '#prefix' => '<strong>',
        '#suffix' => '</strong>',
      ];
      if (!empty($account['organizations'])) {
        $account['value']['organizations'] = [
          '#prefix' => ' @ ',
          '#plain_text' => implode('; ', $account['organizations']),
        ];
      }
      $t_args = [
        '@date' => $this->dateFormatter->formatTimeDiffSince($account['created']),
      ];
      $account['value']['drupal'] = [
        '#prefix' => '<br/>',
        '#markup' => $this->t('On Drupal.org for @date', $t_args),
      ];
      $account['description']['link'] = [
        '#type' => 'link',
        '#title' => $this->t('Configure'),
        '#url' => Url::fromRoute('webform.contribute.settings'),
        '#attributes' => $configure_attributes + ['class' => ['use-ajax']],
      ];
    }
    else {
      $t_args = [
        ':href_register' => 'https://register.drupal.org/user/register',
        ':href_ groups' => 'https://groups.drupal.org',
        ':href_jobs' => 'https://jobs.drupal.org',
        ':href_association' => 'https://www.drupal.org/association',
      ];
      $account['value'] = [
        '#markup' => $this->t('When you <a href=":href_register">create a Drupal.org account</a>, you gain access to a whole ecosystem of Drupal.org sites and services.', $t_args) .
        ' ' .
        $this->t('Your account works on Drupal.org and any of its subsites including <a href=":href_ groups">Drupal Groups</a>, <a href=":href_jobs">Drupal Jobs</a>, <a href=":href_association">Drupal Association</a> and more.', $t_args),
      ];
      $account['description'] = [
        '#type' => 'link',
        '#title' => $this->t('Configure'),
        '#url' => Url::fromRoute('webform.contribute.settings'),
        '#attributes' => $configure_attributes +
        [
          'class' => [
            'use-ajax',
            'button',
            'button--small',
            'button--primary',
            'webform-contribute-community-info__button',
          ],
        ],
      ];
    }

    // Cache account information.
    $this->cache->set($cid, $account, strtotime('+1 hour'), ['webform_contribute']);

    return $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getMembership() {
    $account_type = $this->getAccountType();
    $account_id = $this->getAccountId() ?: 'anonymous';

    $cid = 'webform.contribute.membership.' . md5("$account_type.$account_id");
    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    // Note: Avoiding using the Drupal Association's APIs because they don't
    // allow you to query by user or organization name.
    // @see https://assoc.drupal.org/api/association_members.json
    // @see https://assoc.drupal.org/api/membership/org_partner_memberships.json
    $membership = [];
    $membership['status'] = FALSE;
    if ($account_id != 'anonymous') {
      switch ($account_type) {
        case 'organization':
          if ($badge = $this->getOrganizationBadge()) {
            $membership = [
              'status' => TRUE,
              'badge' => $badge,
            ];
          }
          break;

        case 'user';
          if ($badge = $this->getUserBadge()) {
            $membership = [
              'status' => TRUE,
              'badge' => $badge,
            ];
          }
          break;
      }
    }

    if ($membership['status']) {
      $membership['value'] = $this->t('<strong>You Rock!</strong> Thank you for purchasing a Drupal Association membership.');
    }
    else {
      $t_args = [
        ':href_association' => 'https://www.drupal.org/association',
        ':href_individual' => 'https://www.drupal.org/association/individual-membership',
        ':href_organization' => 'https://www.drupal.org/association/organization-membership',
        ':href_donate' => 'https://www.drupal.org/association/donate',
      ];
      $membership['value'] = ['#markup' => $this->t('The <a href=":href_association">Drupal Association</a> is dedicated to fostering and supporting the Drupal software project.', $t_args)];
      switch ($account_type) {
        case 'organization':
          $membership['value']['#markup'] .= '</br></br>' . $this->t('Organization members are active contributors in the Drupal community. By making your organization or company a member, your team funds the community grants program and you support our work on drupal.org, DrupalCons, and other projects. Together, we unite our global open source community to build and promote Drupal.');
          $membership['description'] = [
            '#type' => 'link',
            '#title' => $this->t('Become an organization member'),
            '#url' => Url::fromUri('https://www.drupal.org/association/organization-membership'),
            '#attributes' => [
              'class' => [
                'button',
                'button--small',
                'button--primary',
                'webform-contribute-community-info__button',
              ],
            ],
          ];
          break;

        case 'user';
          $membership['value']['#markup'] .= '</br></br>' . $this->t('As a member, you build our momentum while supporting the Drupal community.') .
            ' ' . $this->t('Members fund the community grants program and support our team as we work on Drupal.org, DrupalCons, and other projects. Together, we unite our global open source community to build and promote Drupal.');
          $membership['description'] = [
            '#type' => 'link',
            '#title' => $this->t('Become an individual member'),
            '#url' => Url::fromUri('https://www.drupal.org/association/individual-membership'),
            '#attributes' => [
              'class' => [
                'button',
                'button--small',
                'button--primary',
                'webform-contribute-community-info__button',
              ],
            ],
          ];
          break;

        default:
          $membership['value']['#markup'] .= ' ' . $this->t('You can join as an <a href=":href_individual">individual</a> or an <a href=":href_organization">organization member</a>, or <a href=":href_donate">donate</a> directly any amount.', $t_args);
          $membership['description'] = [
            '#type' => 'link',
            '#title' => $this->t('Support our work'),
            '#url' => Url::fromUri('https://www.drupal.org/association/support'),
            '#attributes' => [
              'class' => [
                'button',
                'button--small',
                'webform-contribute-community-info__button',
              ],
            ],
          ];
          break;
      }
    }

    // Cache membership information.
    $this->cache->set($cid, $membership, strtotime('+1 hour'), ['webform_contribute']);

    return $membership;
  }


  /**
   * {@inheritdoc}
   */
  public function getContribution() {
    $account_type = $this->getAccountType();
    $account_id = $this->getAccountId() ?: 'anonymous';

    $cid = 'webform.contribute.contribution.' . md5("$account_type.$account_id");
    $cache = $this->cache->get($cid);
    if ($cache) {
      return $cache->data;
    }

    $contribution = [];
    $contribution['status'] = FALSE;
    if ($account_id != 'anonymous') {
      switch ($account_type) {
        case 'organization':
          $organization = $this->getOrganization();
          $contribution['status'] = ($organization && (!empty($organization['field_contributions']['value']) || !empty($organization['field_org_issue_credit_count'])));
          break;

        case 'user';
          $user = $this->getUser();
          $contribution['status'] = ($user && (!empty($user['field_contributed']) || !empty($user['field_drupal_contributions']['value'])));
          break;
      }
    }

    if ($contribution['status']) {
      $contribution['value'] = $this->t('<strong>You are a Rockstar!</strong> Thank you for contributing back to Drupal.');
    }
    else {
      $t_args = [
        ':href_about' => 'https://www.drupal.org/about',
      ];
      $contribution['value'] = ['#markup' => $this->t('<a href=":href_about">Drupal is an open source project</a>, we don’t have employees to provide Drupal improvements and support. <strong>We depend on our diverse community of passionate volunteers to move the project forward</strong> by working on not just web development and user support but also many other contributions and interests.', $t_args)];
      $contribution['description'] = [
        '#type' => 'link',
        '#title' => $this->t('Ways to get involved'),
        '#url' => Url::fromUri('https://www.drupal.org/contribute'),
        '#attributes' => [
          'class' => [
            'button',
            'button--small',
            'webform-contribute-community-info__button',
          ],
        ],
      ];
      if ($account_type) {
        $contribution['description']['#attributes']['class'][] = 'button--primary';
      }
    }

    // Cache contribution information.
    $this->cache->set($cid, $contribution, strtotime('+1 hour'), ['contribute']);

    return $contribution;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountType() {
    return $this->accountType;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountId() {
    return $this->accountId;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountType($account_type) {
    $this->accountType = $account_type;
  }

  /**
   * {@inheritdoc}
   */
  public function setAccountId($account_id) {
    $this->accountId = $account_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPerson($type) {
    if (isset($this->cachedPeople[$type])) {
      return $this->cachedPeople[$type];
    }

    $people = $this->getPeople($type);
    $person = $people[array_rand($people)];

    $this->cachedPeople[$type] = $person;

    return $person;
  }

  /**
   * {@inheritdoc}
   */
  public function getStyle() {
    $style = '';

    $account = $this->getAccount();
    if ($account && !empty($account['image'])) {
      $style .= '#contribute-info-account:before {background-image: url(' . $account['image'] . ')}';
    }

    $membership = $this->getMembership();
    if ($membership && !empty($membership['badge'])) {
      $style .= '#contribute-info-membership:before {background-image: url(' . $membership['badge'] . ')}';
    }

    $contribution = $this->getContribution();
    if ($contribution && !empty($contribution['status'])) {
      $url = base_path() . drupal_get_path('module', 'webform') . '/contribute/images/icons/drupal.svg';
      $style .= '#contribute-info-contribution:before {background-image: url(' . $url . ')}';
    }

    return $style;
  }

  /****************************************************************************/

  /**
   * Get data from remote server.
   *
   * @param string $uri
   *   The remote URI.
   *
   * @return mixed|null
   *   The returned data. Tequests to *.json files will be decoded.
   */
  protected function get($uri) {
    if (isset($this->cachedData[$uri])) {
      return $this->cachedData[$uri];
    }

    try {
      $response = $this->httpClient->get($uri);
      $is_json = (preg_match('/\.json($|\?)/', $uri)) ? TRUE : FALSE;
      $data = ($is_json) ? Json::decode($response->getBody()) : $response->getBody();
    }
    catch (\Exception $exception) {
      $data = NULL;
    }

    $this->cachedData[$uri] = $data;

    return $data;
  }

  /**
   * Get the user account information from Drupal.org.
   *
   * @return array
   *   The user account information from Drupal.org.
   */
  protected function getUser() {
    $account_id = $this->getAccountId();
    $data = $this->get('https://www.drupal.org/api-d7/user.json?name=' . urlencode($account_id));
    if (isset($data['list']) && count($data['list']) === 1) {
      return reset($data['list']);
    }
    else {
      return [];
    }
  }

  /**
   * Get a user account's picture from Drupal.org.
   *
   * @return string
   *   A user account's picture from Drupal.org.
   */
  protected function getUserPicture() {
    $account_id = $this->getAccountId();
    $body = $this->get('https://www.drupal.org/u/' . urlencode($account_id));
    if (preg_match('#(/user-pictures/picture-[^\.]+.[a-z]+)#', $body, $match)) {
      return 'https://www.drupal.org/files' . $match[1];
    }
    else {
      return '';
    }
  }

  /**
   * Get a user account's badge from Drupal.org.
   *
   * @return string
   *   A user account's badge from Drupal.org.
   */
  protected function getUserBadge() {
    $account_id = $this->getAccountId();
    $body = $this->get('https://www.drupal.org/u/' . urlencode($account_id));
    if (strpos($body, 'association_ind_member_badge.svg') !== FALSE) {
      return 'https://www.drupal.org/sites/all/modules/drupalorg/drupalorg/images/association_ind_member_badge.svg';
    }
    elseif (strpos($body, 'association_org_member_badge.svg') !== FALSE) {
      return 'https://www.drupal.org/sites/all/modules/drupalorg/drupalorg/images/association_org_member_badge.svg';
    }
    else {
      return NULL;
    }
  }

  /**
   * An organization's information from Drupal.org.
   *
   * @return array
   *   An organization's information from Drupal.org.
   */
  protected function getOrganization() {
    $account_id = $this->getAccountId();
    $data = $this->get('https://www.drupal.org/api-d7/node.json?type=organization&title=' . urlencode($account_id));
    if (isset($data['list']) && count($data['list']) === 1) {
      return reset($data['list']);
    }
    else {
      return [];
    }
  }

  /**
   * Get an organization's logo from Drupal.org.
   *
   * @return string
   *   An organization's logo from Drupal.org.
   */
  protected function getOrganizationLogo() {
    $organization = $this->getOrganization();
    if (!$organization || !isset($organization['field_logo']['file']['uri'])) {
      return '';
    }

    $data = $this->get($organization['field_logo']['file']['uri'] . '.json');
    return $data['url'];
  }

  /**
   * Get an organization's membership badge from Drupal.org.
   *
   * @return string
   *   An organization's membership badge from Drupal.org.
   */
  protected function getOrganizationBadge() {
    $organization = $this->getOrganization();
    if (!$organization) {
      return '';
    }

    $body = $this->get($organization['url']);
    if (preg_match('#"(https://www.drupal.org/sites/all/modules/drupalorg/drupalorg/images/association_[^"]+\.svg)"#', $body, $match)) {
      return $match[1];
    }
    else {
      return '';
    }
  }

}
