<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Custom version of skip_on_empty - works in the same way except that "stub" entries are only every Process skipped.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: tg_skip_on_empty
 *     source: filename
 *     method: row         # process is the default
 *     save_to_map: false  # default is true
 * @endcode
 *
 * Can be negated by the use of the "negate: true" property
 *
 * @MigrateProcessPlugin(
 *   id = "tg_skip_on_empty"
 * )
 */
class SkipEmpty extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    $this->configuration['method'] = isset($this->configuration['method']) ? $this->configuration['method'] : 'process';
    if (!in_array($this->configuration['method'], ['process', 'row'])) {
      $msg = $this->errorMessage('Invalid method - must be "process" or "row"');
      throw new RequirementsException($msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    $negate = !empty($this->configuration['negate']);
    switch (TRUE) {
      case Utils::isEmpty($value) == $negate:
        break;
      case $this->configuration['method'] == 'process' || $row->isStub():
        throw new MigrateSkipProcessException();
      default:
        $save_to_map = isset($this->configuration['save_to_map'])
          ? !empty($this->configuration['save_to_map'])
          : TRUE;
        //
        $msg = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
        $msg = $this->errorMessage($msg);
        throw new MigrateSkipRowException($msg, $save_to_map);
    }
    return $value;
  }

}
