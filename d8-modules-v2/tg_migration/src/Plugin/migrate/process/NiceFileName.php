<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a nice file name.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tg_nice_file_name
 *     source: filename
 *     basename: true     # default is false, if true just get base name
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_nice_file_name"
 * )
 */
class NiceFileName extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    list($path, $filename) = Utils::filePathAndName($value);
    $filename = Utils::niceFileName($filename);

    $value = empty($this->configuration['basename']) ? $path . '/' : '';
    $value .= $filename;

    return $value;
  }
}

