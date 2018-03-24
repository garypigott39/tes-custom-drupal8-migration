<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Explode a CSV style string.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   tags:
 *     plugin: tg_explode_csv
 *     source: sourcefield
 *     unique: true  # the default
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_explode_csv"
 * )
 */
class ExplodeCSV extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (Utils::isEmpty($value)) {
      $values = [];
    }
    elseif (!is_string($value)) {
      $msg = $this->errorMessage(sprintf('%s is not a string', var_export($value, TRUE)));
      throw new MigrateException($msg);
    }
    else {
      $values = array_filter( str_getcsv($value) );
      $values = Utils::arrayTrimElements($values);
      if (!isset($this->configuration['unique']) || !empty($this->configuration['unique'])) {
        $values = array_unique($values);
      }
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return TRUE;
  }

}