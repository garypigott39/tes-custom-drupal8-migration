<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Chooses between 2 fields.
 *
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: choose_value
 *     fieldname1: field1
 *     fieldname2: field2
 *     multiple: true  # the default
 *     delimiter: ', '
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "choose_value"
 * )
 */
class ChooseValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty($this->configuration['fieldname1'])) {
      throw new MigrateException('fieldname1 is empty');
    }
    elseif (empty($this->configuration['fieldname2'])) {
      throw new MigrateException('fieldname1 is empty');
    }
    $delimiter = isset($this->configuration['delimiter']) ? $this->configuration['delimiter'] : ', ';
    $multiple  = !empty($this->configuration['multiple']);

    $value1 = $this->_rowFieldValue($field1 = $this->configuration['fieldname1'], $row);
    $value2 = $this->_rowFieldValue($field2 = $this->configuration['fieldname2'], $row);

    // now choose
    $value1 = empty($value1) ? $value2 : $value1;

    // turn into an array
    $value1 = explode($delimiter, $value1);

    // and allow multiple values?
    if (count($value1) > 1 && !$multiple) {
      $value = reset($value1);
    }
    else {
      $value = $value1;
    }

    return implode($delimiter, $value);
  }

  /**
   * Returns source (row) value.
   * @param $name
   * @param \Drupal\migrate\Row $row
   *
   * @return mixed|null
   */
  private function _rowFieldValue($name, Row $row) {
    if ($row->hasSourceProperty($name)) {
      return $row->getSourceProperty($name);
    }
    return NULL;
  }

}
