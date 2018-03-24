<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup as BaseMigrationLookup;
use Drupal\migrate\Row;

/**
 * Custom migration lookup - extends the base migration lookup, the difference
 * is that it supports alternative stub ids based on the fieldnames" argument
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   tags
 *     plugin: tg_migration_lookup
 *     source: tag
 *     fieldnames:
 *       - field1
 *       - field2
 *     migration:
 *       - migration a
 *       - migration b
 *     stub_id:
 *       - if based on field1
 *       - if based on field2
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_migration_lookup"
 * )
 */
class MigrationLookup extends BaseMigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->initConfig($row);

    $this->checkRequirements($row);

    foreach ($this->configuration['fieldnames'] as $key => $fieldname) {
      if ($row->hasSourceProperty($fieldname)) {
        $value = $row->getSourceProperty($fieldname);
        if (!Utils::isEmpty($value)) {
          is_array($this->configuration['stub_id'])
            ? $this->configuration['stub_id'] = $this->configuration['stub_id'][$key]
            : NULL;
          return parent::transform($value, $migrate_executable, $row, $destination_property);
        }
      }
    }

    return NULL;
  }

  /**
   * Make sure that expected vocabularies are supplied.
   * @throws \Drupal\migrate\Exception\RequirementsException;
   */
  private function checkRequirements(Row $row) {
    if (!isset($this->configuration['fieldnames'])) {
      $msg = $this->errorMessage('fieldnames undefined');
      throw new RequirementsException($msg);
    }
    elseif (!is_array($this->configuration['fieldnames'])) {
      $msg = $this->errorMessage('fieldnames must be an array');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['migration'])) {
      $msg = $this->errorMessage('migration undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['stub_id'])) {
      // only need a stub id if multiple migrations
      if (is_array($this->configuration['migration'])) {
        $msg = $this->errorMessage('stub_id undefined');
        throw new RequirementsException($msg);
      }
    }
    elseif (!is_string($this->configuration['stub_id']) && !is_array($this->configuration['stub_id'])) {
      $msg = $this->errorMessage('stub_id must be a string or an array');
      throw new RequirementsException($msg);
    }
    elseif (is_string($this->configuration['stub_id']) && !in_array($this->configuration['stub_id'], $this->configuration['migration'])) {
      $msg = $this->errorMessage('stub_id not found in list of migrations');
      throw new RequirementsException($msg);
    }
    elseif (is_array($this->configuration['stub_id']) && count($this->configuration['fieldnames']) != count($this->configuration['stub_id'])) {
      $msg = $this->errorMessage('fieldnames & stub_id array sizes are different');
      throw new RequirementsException($msg);
    }
  }

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  private function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->pluginId, $msg, $args);
  }

  /**
   * Initialise process plugin config. Basically a (much) simplified version of the
   * get functionality...
   * @param \Drupal\migrate\Row $row
   * @return void
   */
  private function initConfig(Row $row) {
    // running via drush keeps same config, which we've changed!!
    $config = &drupal_static('Drupal\tg_migration\Plugin\migrate\process\MigrationLookup:config', $this->configuration);
    $this->configuration = $config;
    //
    Utils::initProcessConfig($this->configuration, $row);
  }
}
