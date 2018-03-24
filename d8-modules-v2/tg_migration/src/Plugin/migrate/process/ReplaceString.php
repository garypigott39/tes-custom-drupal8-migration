<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Replace named string.
 * @see Core ProcessPluginBase for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * Example:
 *
 * @code
 * process:
 *   body:
 *     plugin:      tg_replace_string
 *     source:      body
 *     regex:       '/ and /i'
 *     replacement: ' & '
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_replace_string"
 * )
 */
class ReplaceString extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (isset($value)) {
      $value = preg_replace($this->configuration['regex'], $this->configuration['replacement'], $value);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    if (!isset($this->configuration['regex'])) {
      throw new RequirementsException('regex undefined');
    }
    elseif (!isset($this->configuration['replacement'])) {
      throw new RequirementsException('replacement undefined');
    }
  }

}
