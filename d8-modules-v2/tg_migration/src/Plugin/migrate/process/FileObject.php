<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Get file object for supplied fid. Used in cases (like media) where we need the file details.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   temp_file_object:
 *     plugin: tg_file_object
 *     source: fid
 *     array: true  # default is object format
 *     skip: row    # default is process
 *     empty_message: 'File object not found'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_file_object"
 * )
 */
class FileObject extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    // some default config
    if (!isset($this->configuration['skip'])) {
      $this->configuration['skip'] = 'process';
    }
    if (!isset($this->configuration['message'])) {
      $this->configuration['message'] = '';
    }

    // handle the value
    switch (TRUE) {
      case !Utils::isNumeric($value):
        $msg = $this->errorMessage('Empty/non numeric fid supplied');
        throw new RequirementsException($msg);
      case $file = Utils::getFileObject($value):
        $value = $file;
        break;
      case $this->configuration['skip'] == 'row':
        $msg = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
        $msg = $this->errorMessage($msg);
        throw new MigrateSkipRowException($msg);
      default:
        throw new MigrateSkipProcessException();
    }

    return !empty($this->configuration['array']) ? (array) $value : $value;
  }

}
