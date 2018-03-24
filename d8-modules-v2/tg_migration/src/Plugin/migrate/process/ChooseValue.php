<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Choose between multiple fields.
 * @see Core ProcessPluginBase for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: tg_choose_value
 *     fieldnames:
 *       - field1
 *       - field2
 *       - field3
 *     multiple: true  # the default
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_choose_value"
 * )
 */
class ChooseValue extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    $multiple = !empty($this->configuration['multiple']);

    foreach ($this->configuration['fieldnames'] as $fieldname) {
      if ($row->hasSourceProperty($fieldname)) {
        $fieldvalue = $row->getSourceProperty($fieldname);
        if (isset($fieldvalue) && !Utils::isEmpty($fieldvalue)) {
          $value = ($multiple || !is_array($fieldvalue)) ? $fieldvalue : reset($fieldvalue);
          break;  // out of loop!
        }
      }
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    if (!isset($this->configuration['fieldnames'])) {
      throw new RequirementsException('fieldnames undefined');
    }
  }
}
