<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Tags;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Custom news tags migration lookup - uses vocabularies first. Expected source is
 * a taxonomy term name.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   tags
 *     plugin: tg_news_tags_migration_lookup
 *     source: tag
 *     tags_xref:
 *       - vocab name
 *       - vocab name
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_news_tags_migration_lookup"
 * )
 */
class NewsTagsMigrationLookup extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->initConfig($row);

    $this->checkRequirements($row);

    $vocabs = isset($this->configuration['tags_xref']) ? $this->configuration['tags_xref'] : [];

    $values = !is_array($value) ? [$value] : $value;
    if (!empty($values)) {
      $found = [];
      foreach ($values as $name) {
        if ($tid = Tags::getXref($name, $vocabs)) {
          $found[] = $tid;
        }
        elseif ($stubby = parent::transform($name, $migrate_executable, $row, $destination_property)) {
          $found[] = $stubby;
        }
      }
      $value = !$this->multiple() && is_array($found) ? array_shift($found) : $found;
    }

    return $value;
  }

  /**
   * Make sure that expected vocabularies are supplied.
   * @throws \Drupal\migrate\Exception\RequirementsException;
   */
  private function checkRequirements(Row $row) {
    if (!isset($this->configuration['tags_xref'])) {
      $msg = $this->errorMessage('tags_xref undefined');
      throw new RequirementsException($msg);
    }
    elseif (!is_array($this->configuration['tags_xref'])) {
      $msg = $this->errorMessage('tags_xref must be an array');
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
    Utils::initProcessConfig($this->configuration, $row);
  }

}
