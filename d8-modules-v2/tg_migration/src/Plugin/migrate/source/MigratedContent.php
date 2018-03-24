<?php

namespace Drupal\tg_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Custom class MigratedContent extends SourcePluginBase version.
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_migrated_content
 *   table: file_managed
 *   fields:
 *     fid:      'File id'
 *     uid:      'User id'
 *     filename: 'File name'
 *     uri:      'Uri'
 *     filemime: 'File mime type'
 *     status:   'File status'
 *     created:  'File created timestamp'
 *     type:     'File type'
 *   migration:
 *     - news_sql_teasers
 *     - news_sql_heroes
 *   join_on_field: fid
 *   source_type: integer
 * @endcode
 *
 * @MigrateSource(
 *   id = "tg_migrated_content",
 * )
 */
class MigratedContent extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    $this->getDatabase();
    //
    parent::checkRequirements();
    if (!isset($this->database)) {
      $msg = $this->errorMessage('Unable to connect to source database, or tables missing');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['table']) || !Query::tableExists($this->database, $this->configuration['table'])) {
      $msg = $this->errorMessage('table invalid/undefined"');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['fields']) || !is_array($this->configuration['fields'])) {
      $msg = $this->errorMessage('fields array undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['join_on_field']) || !is_string($this->configuration['join_on_field'])) {
      $msg = $this->errorMessage('join_on_field undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['source_type']) || !in_array($this->configuration['source_type'], ['integer', 'string'])) {
      $msg = $this->errorMessage('source_type is invalid/undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['migration']) || !is_array($this->configuration['migration'])) {
      $msg = $this->errorMessage('migration array undefined');
      throw new RequirementsException($msg);
    }
    else {
      $migrations = is_array($this->configuration['migration']) ? $this->configuration['migration'] : [ $this->configuration['migration'] ];
      foreach ($migrations as $mid) {
        $table = 'migrate_map_' . $mid;
        if (!Query::tableExists($this->database, $table)) {
          $msg = $this->errorMessage('invalid migration map table @table', [
            '@table' => $table,
          ]);
          throw new RequirementsException($msg);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDatabase() {
    // we will be using the default database
    $this->database = \Drupal\Core\Database\Database::getConnection();
    //
    return $this->database;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select($this->configuration['table'], 'target');
    foreach (array_keys($this->configuration['fields']) as $fieldname) {
      $query->addField('target', $fieldname);
    }

    if ($subquery = $this->subQuery()) {
      $query->join($subquery, 'unionmap', 'unionmap.destid = target.' . $this->configuration['join_on_field']);
      $query->addField('unionmap', 'sourceid');
    }

    $query->orderBy(1);
    $query->distinct();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->contentFields() + [
      'sourceid' => t('Source identifier'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [
      'sourceid' => [
        'type' => $this->configuration['source_type'],
        'alias' => 'unionmap',
      ]
    ];
    return $ids;
  }

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  private function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->migration->id(), $msg, $args);
  }

  /**
   * Returns array of file managed fields of interest.
   * @return array
   */
  private function contentFields() {
    $ret = [];
    foreach ($this->configuration['fields'] as $fieldname => $fieldtitle) {
      $ret[$fieldname] = t($fieldtitle);
    }
    return $ret;
  }

  /**
   * Build subquery UNION select.
   * @return \Drupal\Core\Database\Query\SelectInterface | null
   */
  private function subQuery() {
    $migrations = is_array($this->configuration['migration'])
      ? $this->configuration['migration']
      : [ $this->configuration['migration'] ];

    $query = NULL;

    foreach ($migrations as $mid) {
      $table = 'migrate_map_' . $mid;
      $subquery = $this->select($table, 'map');
      $subquery->addField('map', 'sourceid1', 'sourceid');
      $subquery->addField('map', 'destid1', 'destid');
      $subquery->isNotNull('map.destid1');
      $subquery->condition('map.source_row_status', [MigrateIdMapInterface::STATUS_IMPORTED, MigrateIdMapInterface::STATUS_NEEDS_UPDATE], 'IN');
      $query = isset($query) ? $query->union($subquery) : $subquery;
    }

    return $query;
  }
}
