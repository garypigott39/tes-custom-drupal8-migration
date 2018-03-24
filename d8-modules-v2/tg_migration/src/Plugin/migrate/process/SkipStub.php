<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateSkipRowException;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Custom version of skip_on_empty - but this time for stubs.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: tg_skip_on_stub
 *     source: filename
 *     method: row  # process is the default
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_skip_on_stub"
 * )
 */
class SkipStub extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
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
    switch (TRUE) {
      case !$row->isStub():
        break;
      case $this->configuration['method'] == 'process' || $row->isStub():
        throw new MigrateSkipProcessException();
      default:
        $msg = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
        $msg = $this->errorMessage($msg);
        throw new MigrateSkipRowException($msg);
    }
    return $value;
  }

}
