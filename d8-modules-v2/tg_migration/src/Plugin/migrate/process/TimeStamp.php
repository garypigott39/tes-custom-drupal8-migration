<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Format datetime field - "yyyy-mm-ddThh:mm:ss" format
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: tg_timestamp
 *     source: datefield
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_timestamp"
 * )
 */
class TimeStamp extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if ($timestamp = strtotime($value)) {
      $value = date('Y-m-d', $timestamp) . 'T' .date( 'H:i:s', $timestamp);
    }
    else {
      $value = NULL;
    }

    return $value;
  }

}
