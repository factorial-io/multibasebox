<?php

namespace Drupal\webform\Utility;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\OptGroup;

/**
 * Helper class webform date helper methods.
 */
class WebformDateHelper {

  /**
   * Cached interval options.
   *
   * @var array
   */
  protected static $intervalOptions;

  /**
   * Cached interval options flattened.
   *
   * @var array
   */
  protected static $intervalOptionsFlattened;

  /**
   * Wrapper for DateFormatter that return an empty string for empty timestamps.
   *
   * @param int $timestamp
   *   A UNIX timestamp to format.
   * @param string $type
   *   (optional) The data format to use.
   * @param string $format
   *   (optional) If $type is 'custom', a PHP date format string suitable for
   *   element to date(). Use a backslash to escape ordinary text, so it does
   *   not get interpreted as date format characters.
   * @param string|null $timezone
   *   (optional) Time zone identifier, as described at
   *   http://php.net/manual/timezones.php Defaults to the time zone used to
   *   display the page.
   * @param string|null $langcode
   *   (optional) Language code to translate to. NULL (default) means to use
   *   the user interface language for the page.
   *
   * @return string
   *   A translated date string in the requested format.  An empty string
   *   will be returned for empty timestamps.
   *
   * @see \Drupal\Core\Datetime\DateFormatterInterface::format
   */
  public static function format($timestamp, $type = 'fallback', $format = '', $timezone = NULL, $langcode = NULL) {
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    return $timestamp ? $date_formatter->format($timestamp, $type) : '';
  }

  /**
   * Format date/time object to be written to the database using 'Y-m-d\TH:i:s'.
   *
   * @param \Drupal\Core\Datetime\DrupalDateTime $date
   *   A DrupalDateTime object.
   *
   * @return string
   *   The date/time object format as 'Y-m-d\TH:i:s'.
   */
  public static function formatStorage(DrupalDateTime $date) {
    return $date->format(DATETIME_DATETIME_STORAGE_FORMAT);
  }

  /**
   * Check if date/time string is using a valid date/time format.
   *
   * @param string $time
   *   A date/time string.
   * @param string $format
   *   Format accepted by date().
   *
   * @return bool
   *   TRUE is $time is in the accepted format.
   *
   * @see http://stackoverflow.com/questions/19271381/correctly-determine-if-date-string-is-a-valid-date-in-that-format
   */
  public static function isValidDateFormat($time, $format = 'Y-m-d') {
    $datetime = \DateTime::createFromFormat($format, $time);
    return ($datetime && $datetime->format($format) === $time);
  }

  /**
   * Get interval options used by submission limits.
   *
   * @return array
   *   An associative array of interval options.
   */
  public static function getIntervalOptions() {
    self::initIntervalOptions();
    return self::$intervalOptions;
  }

  /**
   * Get interval options used by submission limits.
   *
   * @return array
   *   An associative array of interval options.
   */
  public static function getIntervalOptionsFlattened() {
    self::initIntervalOptions();
    return self::$intervalOptionsFlattened;
  }

  /**
   * Get interval text.
   *
   * @param int|null $interval
   *   An interval.
   *
   * @return string
   *   An intervals' text.
   */
  public static function getIntervalText($interval) {
    $interval = ((string) $interval) ?: '';
    $intervals = self::getIntervalOptionsFlattened();
    return (isset($intervals[$interval])) ? $intervals[$interval] : $intervals[''];
  }

  /**
   * Initialize interval options used by submission limits.
   */
  protected static function initIntervalOptions() {
    if (!isset(self::$intervalOptions)) {
      $options = ['' => t('ever')];

      // Minute.
      $minute = 60;
      $minute_optgroup = (string) t('Minute');
      $options[$minute_optgroup][$minute] = t('every minute');
      $increment = 5;
      while ($increment < 60) {
        $increment += 5;
        $options[$minute_optgroup][($increment * $minute)] = t('every @increment minutes', ['@increment' => $increment]);
      }

      // Hour.
      $hour = $minute * 60;
      $hour_optgroup = (string) t('Hour');
      $options[$hour_optgroup][$hour] = t('every hour');
      $increment = 1;
      while ($increment < 24) {
        $increment += 1;
        $options[$hour_optgroup][($increment * $hour)] = t('every @increment hours', ['@increment' => $increment]);
      }

      // Day.
      $day = $hour * 24;
      $day_optgroup = (string) t('Day');
      $options[$day_optgroup][$day] = t('every day');
      $increment = 1;
      while ($increment < 7) {
        $increment += 1;
        $options[$day_optgroup][($increment * $day)] = t('every @increment days', ['@increment' => $increment]);
      }

      // Week.
      $week = $day * 7;
      $week_optgroup = (string) t('Week');
      $options[$week_optgroup][$week] = t('every week');
      $increment = 1;
      while ($increment < 52) {
        $increment += 1;
        $options[$week_optgroup][($increment * $week)] = t('every @increment weeks', ['@increment' => $increment]);
      }

      // Year.
      $year = $day * 365;
      $year_optgroup = (string) t('Year');
      $options[$year_optgroup][$year] = t('every year');
      $increment = 1;
      while ($increment < 10) {
        $increment += 1;
        $options[$year_optgroup][($increment * $year)] = t('every @increment years', ['@increment' => $increment]);
      }

      self::$intervalOptions = $options;
      self::$intervalOptionsFlattened = OptGroup::flattenOptions($options);
    }
  }

}
