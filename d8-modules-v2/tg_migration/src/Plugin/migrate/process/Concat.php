<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Concatenate strings.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   file_path:
 *     plugin: tg_concat
 *     source: 
 *       - 'https://tes.com'
 *       - filename
 *     delimiter: '/'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_concat"
 * )
 */
class Concat extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (is_array($value)) {
      $delimiter = isset($this->configuration['delimiter']) ? $this->configuration['delimiter'] : '';
      $value = array_map( function($string) use ($delimiter) {
        return rtrim($string, $delimiter);
      }, $value);
      $value = implode($delimiter, $value);
    }
    return $value;
  }

}
