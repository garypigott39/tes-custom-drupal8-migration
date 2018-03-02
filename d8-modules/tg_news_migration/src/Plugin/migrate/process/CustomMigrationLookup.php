<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Custom migrate lookup -- just to facilitate debugging!!!
 *
 * @MigrateProcessPlugin(
 *   id = "custom_migration_lookup"
 * )
 */
class CustomMigrationLookup extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $ret = NULL;
    $value = trim($value);
    if (strlen($value) > 0) {
      $ret = parent::transform($value, $migrate_executable, $row, $destination_property);
    }
    // @todo:
    return $ret;
  }

}
