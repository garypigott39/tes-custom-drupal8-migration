<?php

namespace Drupal\tg_news_migration\Plugin\migrate\destination;

use Drupal\migrate\Plugin\migrate\destination\NullDestination;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Provides null destination plugin.
 *
 * @MigrateDestination(
 *   id = "do_nothing",
 *   requirements_met = true
 * )
 */
class DoNothing extends NullDestination {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
  }

}
