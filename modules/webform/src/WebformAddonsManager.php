<?php

namespace Drupal\webform;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Webform add-ons manager.
 */
class WebformAddonsManager implements WebformAddonsManagerInterface {

  use StringTranslationTrait;

  /**
   * Projects that provides additional functionality to the Webform module.
   *
   * @var array
   */
  protected $projects;

  /**
   * Constructs a WebformAddOnsManager object.
   */
  public function __construct() {
    $this->projects = $this->initProjects();
  }

  /**
   * {@inheritdoc}
   */
  public function getProject($name) {
    return $this->projects[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getProjects($category = NULL) {
    $projects = $this->projects;
    if ($category) {
      foreach ($projects as $project_name => $project) {
        if ($project['category'] != $category) {
          unset($projects[$project_name]);
        }
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getThirdPartySettings() {
    $projects = $this->projects;
    foreach ($projects as $project_name => $project) {
      if (empty($project['third_party_settings'])) {
        unset($projects[$project_name]);
      }
    }
    return $projects;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategories() {
    $categories = [];
    $categories['config'] = [
      'title' => $this->t('Configuration management'),
    ];
    $categories['element'] = [
      'title' => $this->t('Elements'),
    ];
    $categories['enhancement'] = [
      'title' => $this->t('Enhancements'),
    ];
    $categories['integration'] = [
      'title' => $this->t('Integration'),
    ];
    $categories['mail'] = [
      'title' => $this->t('Mail'),
    ];
    $categories['migrate'] = [
      'title' => $this->t('Migrate'),
    ];
    $categories['multilingual'] = [
      'title' => $this->t('Multilingual'),
    ];
    $categories['rest'] = [
      'title' => $this->t('REST'),
    ];
    $categories['spam'] = [
      'title' => $this->t('SPAM Protection'),
    ];
    $categories['submission'] = [
      'title' => $this->t('Submissions'),
    ];
    $categories['validation'] = [
      'title' => $this->t('Validation'),
    ];
    $categories['utility'] = [
      'title' => $this->t('Utility'),
    ];
    $categories['workflow'] = [
      'title' => $this->t('Workflow'),
    ];
    $categories['development'] = [
      'title' => $this->t('Development'),
    ];
    return $categories;
  }

  /**
   * Initialize add-on projects.
   *
   * @return array
   *   An associative array containing add-on projects.
   */
  protected function initProjects() {
    $projects = [];

    // Config: Drush CMI tools.
    $projects['drush_cmi_tools'] = [
      'title' => $this->t('Drush CMI tools'),
      'description' => $this->t('Provides advanced CMI import and export functionality for CMI workflows. Drush CMI tools should be used to protect Forms from being overwritten during a configuration import.'),
      'url' => Url::fromUri('https://github.com/previousnext/drush_cmi_tools'),
      'category' => 'config',
    ];

    // Config: Configuration Ignore.
    $projects['config_ignore'] = [
      'title' => $this->t('Config Ignore'),
      'description' => $this->t('Ignore certain configuration during import'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_ignore'),
      'category' => 'config',
    ];

    // Config: Configuration Split.
    $projects['config_split'] = [
      'title' => $this->t('Configuration Split'),
      'description' => $this->t('Provides configuration filter for importing and exporting split config.'),
      'url' => Url::fromUri('https://www.drupal.org/project/config_split'),
      'category' => 'config',
      'recommended' => TRUE,
    ];

    // Element: Webform Composite Tools.
    $projects['webform_composite'] = [
      'title' => $this->t('Webform Composite Tools'),
      'description' => $this->t("Provides a reusable composite element for use on webforms."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_composite'),
      'category' => 'element',
    ];
    
    // Element: Webform Checkboxes Table.
    $projects['webform_checkboxes_table'] = [
      'title' => $this->t('Webform Checkboxes Table'),
      'description' => $this->t('Displays checkboxes element in a table grid.'),
      'url' => Url::fromUri('https://github.com/minnur/webform_checkboxes_table'),
      'category' => 'element',
    ];

    // Element: Webform Crafty Clicks.
    $projects['webform_craftyclicks'] = [
      'title' => $this->t('Webform Crafty Clicks'),
      'description' => $this->t('Adds Crafty Clicks UK postcode lookup to the Webform Address composite element.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_craftyclicks'),
      'category' => 'element',
    ];

    // Element: Webform Layout Container.
    $projects['webform_layout_container'] = [
      'title' => $this->t('Webform Layout Container'),
      'description' => $this->t("Provides a layout container element to add to a webform, which uses old fashion floats to support legacy browsers that don't support CSS Flexbox (IE9 and IE10)."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_layout_container'),
      'category' => 'element',
    ];

    // Element: Webform Node Element.
    $projects['webform_node_element'] = [
      'title' => $this->t('Webform Node Element'),
      'description' => $this->t("Provides a 'Node' element to display node content as an element on a webform. Can be modified dynamically using an event handler."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_node_element'),
      'category' => 'element',
    ];

    // Element: Webform Score.
    $projects['webform_score'] = [
      'title' => $this->t('Webform Score'),
      'description' => $this->t("Lets you score an individual user's answers, then store and display the scores."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_score'),
      'category' => 'element',
    ];

    // Element: Webform Simple Hierarchical Select.
    $projects['webform_shs'] = [
      'title' => $this->t('Webform Simple Hierarchical Select'),
      'description' => $this->t("Integrates Simple Hierarchical Select module with Webform."),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_shs'),
      'category' => 'element',
    ];

    // Enhancement: Webform Wizard Full Title.
    $projects['webform_wizard_full_title'] = [
      'title' => $this->t('Webform Wizard Full Title'),
      'description' => $this->t('Extends functionality of Webform so on wizard forms, the title of the wizard page can override the form title'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_wizard_full_title'),
      'category' => 'enhancement',
    ];

    // Integration: Webform HubSpot.
    $projects['hubspot'] = [
      'title' => $this->t('Webform HubSpot'),
      'description' => $this->t('Provides HubSpot leads API integration with Drupal.'),
      'url' => Url::fromUri('https://www.drupal.org/project/hubspot'),
      'category' => 'integration',
    ];

    // Integrations: Micro Webform.
    $projects['micro_webform'] = [
      'title' => $this->t('Micro Webform'),
      'description' => $this->t('Integrate webform module with a micro site.'),
      'url' => Url::fromUri('https://www.drupal.org/project/micro_webform'),
      'category' => 'integration',
    ];

    // Integration: Webform iContact.
    $projects['webform_icontact'] = [
      'title' => $this->t('Webform iContact'),
      'description' => $this->t('Send Webform submissions to iContact list.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/ibakayoko/2853326'),
      'category' => 'integration',
    ];

    // Integrations: Webform MailChimp.
    $projects['webform_mailchimp'] = [
      'title' => $this->t('Webform MailChimp'),
      'description' => $this->t('Posts form submissions to MailChimp list.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mailchimp'),
      'category' => 'integration',
    ];

    // Integrations: Webform Product.
    $projects['webform_product'] = [
      'title' => $this->t('Webform Product'),
      'description' => $this->t('Links commerce products to webform elements.'),
      'url' => Url::fromUri('https://github.com/chx/webform_product'),
      'category' => 'integration',
    ];

    // Integrations: Webform Simplenews Handler.
    $projects['webform_simplenews_handler'] = [
      'title' => $this->t('Webform Simplenews Handler'),
      'description' => $this->t('Provides a Webform Handler called "Submission Newsletter" that allows to link webform submission to one or more Simplenews newsletter subscriptions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_simplenews_handler'),
      'category' => 'integration',
    ];

    // Integrations: Webform Slack integration.
    $projects['webform_slack'] = [
      'title' => $this->t('Webform Slack'),
      'description' => $this->t('Provides a Webform handler for posting a message to a slack channel when a submission is saved.'),
      'url' => Url::fromUri('https://www.drupal.org/sandbox/smaz/2833275'),
      'category' => 'integration',
    ];

    // Integrations: Webform Stripe integration.
    $projects['stripe_webform'] = [
      'title' => $this->t('Webform Stripe'),
      'description' => $this->t('Provides a stripe webform element and default handlers.'),
      'url' => Url::fromUri('https://www.drupal.org/project/stripe_webform'),
      'category' => 'integration',
    ];

    // Integrations: Webform SugarCRM Integration.
    $projects['webform_sugarcrm'] = [
      'title' => $this->t('Webform SugarCRM Integration'),
      'description' => $this->t('Provides integration for webform submission with SugarCRM.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sugarcrm'),
      'category' => 'integration',
    ];

    // Integrations: OpenInbound for Drupal.
    $projects['openinbound'] = [
      'title' => $this->t('OpenInbound for Drupal'),
      'description' => $this->t('OpenInbound tracks contacts and their interactions on websites.'),
      'url' => Url::fromUri('https://www.drupal.org/project/openinbound'),
      'category' => 'integration',
    ];

    // Integrations: Salesforce Web-to-Lead Webform Data Integration.
    $projects['sfweb2lead_webform'] = [
      'title' => $this->t('Salesforce Web-to-Lead Webform Data Integration'),
      'description' => $this->t('Integrates Salesforce Web-to-Lead Form feature with various webforms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/sfweb2lead_webform'),
      'category' => 'integration',
    ];

    // Mail: Mail System.
    $projects['mailsystem'] = [
      'title' => $this->t('Mail System'),
      'description' => $this->t('Provides a user interface for per-module and site-wide mail system selection.'),
      'url' => Url::fromUri('https://www.drupal.org/project/mailsystem'),
      'category' => 'mail',
    ];

    // Mail: Webform Mass Email.
    $projects['webform_mass_email'] = [
      'title' => $this->t('Webform Mass Email'),
      'description' => $this->t('Provides a functionality to send mass email for the subscribers of a webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_mass_email'),
      'category' => 'mail',
    ];

    // Mail: SMTP Authentication Support.
    $projects['smtp'] = [
      'title' => $this->t('SMTP Authentication Support'),
      'description' => $this->t('Allows for site emails to be sent through an SMTP server of your choice.'),
      'url' => Url::fromUri('https://www.drupal.org/project/smtp'),
      'category' => 'mail',
    ];

    // Multilingual: Lingotek Translation.
    $projects['lingotek'] = [
      'title' => $this->t('Lingotek Translation.'),
      'description' => $this->t('Translates content, configuration, and interface using the Lingotek Translation Management System.'),
      'url' => Url::fromUri('https://www.drupal.org/project/lingotek'),
      'category' => 'multilingual',
    ];

    // Migrate: Webform Migrate.
    $projects['webform_migrate'] = [
      'title' => $this->t('Webform Migrate'),
      'description' => $this->t('Provides migration routines from d6, d7 webform to d8 webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_migrate'),
      'category' => 'migrate',
      'recommended' => TRUE,
    ];

    // Spam: Antibot.
    $projects['antibot'] = [
      'title' => $this->t('Antibot'),
      'description' => $this->t('Prevent forms from being submitted without JavaScript enabled.'),
      'url' => Url::fromUri('https://www.drupal.org/project/antibot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
    ];

    // Spam: CAPTCHA.
    $projects['captcha'] = [
      'title' => $this->t('CAPTCHA'),
      'description' => $this->t('Provides CAPTCHA for adding challenges to arbitrary forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/captcha'),
      'category' => 'spam',
      'recommended' => TRUE,

    ];

    // Spam: Honeypot.
    $projects['honeypot'] = [
      'title' => $this->t('Honeypot'),
      'description' => $this->t('Mitigates spam form submissions using the honeypot method.'),
      'url' => Url::fromUri('https://www.drupal.org/project/honeypot'),
      'category' => 'spam',
      'third_party_settings' => TRUE,
      'recommended' => TRUE,
    ];

    // Submissions: Webform Views Integration.
    $projects['webform_views'] = [
      'title' => $this->t('Webform Views'),
      'description' => $this->t('Integrates Webform 8.x-5.x and Views modules.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_views'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Submissions: Webform Analysis.
    $projects['webform_analysis'] = [
      'title' => $this->t('Webform Analysis'),
      'description' => $this->t('Used to obtain statistics on the results of form submissions.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_analysis'),
      'category' => 'submission',
      'recommended' => TRUE,
    ];

    // Webform Invitation.
    $projects['webform_invitation'] = [
      'title' => $this->t('Webform Invitation'),
      'description' => $this->t('Allows you to restrict submissions to a webform by generating codes (which may then be distributed e.g. by email to participants).'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_invitation'),
      'category' => 'submission',
    ];

    // Submissions: Webform Permissions By Term.
    $projects['webform_permissions_by_term'] = [
      'title' => $this->t('Webform Permissions By Term'),
      'description' => $this->t('Extends the functionality of Permissions By Term to be able to limit the webform submissions access by users or roles.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_permissions_by_term'),
      'category' => 'submission',
    ];

    // Submissions: Webform Sanitize.
    $projects['webform_sanitize'] = [
      'title' => $this->t('Webform Sanitize'),
      'description' => $this->t('Sanitizes submissions to remove potentially sensitive data.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_sanitize'),
      'category' => 'submission',
    ];

    // Submissions: Webform Scheduled Tasks.
    $projects['webform_scheduled_tasks'] = [
      'title' => $this->t('Webform Scheduled Tasks'),
      'description' => $this->t('Allows the regular cleansing/sanitization of sensitive fields in Webform.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_scheduled_tasks'),
      'category' => 'submission',
    ];

    // Submissions: Webform Queue.
    $projects['webform_queue'] = [
      'title' => $this->t('Webform Queue'),
      'description' => $this->t('Posts form submissions into a Drupal queue.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_queue'),
      'category' => 'submission',
    ];

    // REST: Webform REST.
    $projects['webform_rest'] = [
      'title' => $this->t('Webform REST'),
      'description' => $this->t('Retrieve and submit webforms via REST.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_rest'),
      'category' => 'rest',
    ];

    // Utility: Webform Encrypt.
    $projects['wf_encrypt'] = [
      'title' => $this->t('Webform Encrypt'),
      'description' => $this->t('Provides encryption for webform elements.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_encrypt'),
      'category' => 'utility',
    ];

    // Utility: Webform Ip Track.
    $projects['webform_ip_track'] = [
      'title' => $this->t('Webform Ip Track'),
      'description' => $this->t('Ip Location details as custom tokens to use in webform submission values.'),
      'url' => Url::fromUri('https://www.drupal.org/project/webform_ip_track'),
      'category' => 'utility',
    ];

    // Utility: IMCE.
    $projects['imce'] = [
      'title' => $this->t('IMCE'),
      'description' => $this->t('IMCE is an image/file uploader and browser that supports personal directories and quota.'),
      'url' => Url::fromUri('https://www.drupal.org/project/imce'),
      'category' => 'utility',
      'install' => TRUE,
      'recommended' => TRUE,
    ];

    // Utility: Token.
    $projects['token'] = [
      'title' => $this->t('Token'),
      'description' => $this->t('Provides a user interface for the Token API and some missing core tokens.'),
      'url' => Url::fromUri('https://www.drupal.org/project/token'),
      'category' => 'utility',
      'install' => TRUE,
      'recommended' => TRUE,
    ];

    // Validation: Clientside Validation.
    $projects['clientside_validation'] = [
      'title' => $this->t('Clientside Validation'),
      'description' => $this->t('Adds clientside validation to forms.'),
      'url' => Url::fromUri('https://www.drupal.org/project/clientside_validation'),
      'category' => 'validation',
      'recommended' => TRUE,
    ];

    // Validation: Validators.
    $projects['validators'] = [
      'title' => $this->t('Validators'),
      'description' => $this->t('Provides Symfony (form) Validators for Drupal 8.'),
      'url' => Url::fromUri('https://www.drupal.org/project/validators'),
      'category' => 'validation',
    ];

    // Workflow: Maestro.
    $projects['maestro'] = [
      'title' => $this->t('Maestro Workflow Engine'),
      'description' => $this->t('A business process workflow solution that allows you to create and automate a sequence of tasks representing any business, document approval or collaboration process.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maestro'),
      'category' => 'workflow',
      'recommended' => TRUE,
    ];

    // Devel: Maillog / Mail Developer.
    $projects['maillog'] = [
      'title' => $this->t('Maillog / Mail Developer'),
      'description' => $this->t('Utility to log all Mails for debugging purposes. It is possible to suppress mail delivery for e.g. dev or staging systems.'),
      'url' => Url::fromUri('https://www.drupal.org/project/maillog'),
      'category' => 'development',
      'recommended' => TRUE,
    ];

    return $projects;
  }

}
