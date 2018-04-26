<?php

namespace Drupal\webform_scheduled_email\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;
use Drupal\webform\Entity\Webform;
use Drupal\webform_scheduled_email\Plugin\WebformHandler\ScheduleEmailWebformHandler;

/**
 * Webform scheduled email commands for Drush 9.x.
 */
class WebformScheduledEmailCommands extends DrushCommands {

  /**
   * @hook validate webform:scheduled-email:cron
   */
  public function drush_webform_scheduled_email_cron_validate(CommandData $commandData) {
    $arguments = $commandData->arguments();
    $webform_id = $arguments['webform_id'];
    $handler_id = $arguments['handler_id'];

    // Get and validate optional $webform_id parameter.
    $webform = NULL;
    if ($webform_id) {
      $webform = Webform::load($webform_id);
      if (!$webform) {
        throw new \Exception(dt('Webform @id not recognized.', ['@id' => $webform_id]));
      }
    }

    // Get and validate optional $handler_id parameter.
    if ($handler_id) {
      try {
        $handler = $webform->getHandler($handler_id);
      }
      catch (\Exception $exception) {
        throw new \Exception(dt('Handler @id not recognized.', ['@id' => $handler_id]));
      }
      if (!($handler instanceof ScheduleEmailWebformHandler)) {
        throw new \Exception(dt('Handler @id is not a scheduled email handler.', ['@id' => $handler_id]));
      }
    }
  }

  /**
   * Executes cron task for webform scheduled emails.
   *
   * @command webform:scheduled-email:cron
   * @param $webform_id (optional)
   *   The webform ID you want the cron task to be executed for
   * @param $handler_id (optional)
   *   The handler ID you want the cron task to be executed for
   * @option schedule_limit
   *   The maximum number of emails to be scheduled. If set to 0 no emails will be scheduled. (Default 1000)
   * @option send_limit
   *   The maximum number of emails to be sent. If set to 0 no emails will be sent. (Default 500)
   * @aliases wfsec
   */
  public function drush_webform_scheduled_email_cron($webform_id = NULL, $handler_id = NULL, array $options = ['schedule_limit' => 1000, 'send_limit' => 500]) {
    $webform = ($webform_id) ? Webform::load($webform_id) : NULL;
    /** @var \Drupal\webform_scheduled_email\WebformScheduledEmailManagerInterface $webform_scheduled_email_manager */
    $webform_scheduled_email_manager = \Drupal::service('webform_scheduled_email.manager');
    $stats = $webform_scheduled_email_manager->cron(
      $webform,
      $handler_id,
      $options['schedule_limit'],
      $options['send_limit']
    );
    $this->output()->writeln(dt($stats['_message'], $stats['_context']));
  }

}
