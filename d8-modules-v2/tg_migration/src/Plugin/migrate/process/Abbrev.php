<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Abbreviate supplied string.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tg_abbrev
 *     source: title
 *     length: 100    # default
 *     elipse: '...'  # default is ''
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_abbrev"
 * )
 */
class Abbrev extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (is_string($value)) {
      $elipse = isset($this->configuration['elipse']) ? $this->configuration['elipse'] : '';
      $length = isset($this->configuration['length']) ? $this->configuration['length'] : 100;
      $value = Utils::abbrev($value, $length, $elipse);
    }
    return $value;
  }
}

