<?php

namespace Drupal\tg_news2_migration\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

class D7Query {

  /**
   * Get managed file details.
   * @param $database
   * @param $fid
   * @return bool
   */
  public static function getFileObject(Connection $database, $fid) {
    if (isset($database)) {
      $query = $database->select('file_managed', 'f');
      $query->fields('f');
      $query->condition('f.fid', $fid);
      $query->condition('f.status', 1);

      foreach ($query->execute() as $file_object) {
        return $file_object;
      }
    }
    return FALSE;
  }

  /**
   * Initialise node query.
   * @param $query - by reference
   * @param $node_type
   * @param $configuration
   * @return void
   */
  public static function nodeQuery(SelectInterface &$query, $node_type, $configuration) {
    $query->condition('n.type', $node_type);

    $query->leftjoin('field_data_body', 'body', 'n.nid = body.entity_id AND body.entity_type = :type AND body.bundle = n.type AND body.deleted = 0 AND body.delta = 0', [
        ':type' => 'node',
      ]);

    // additional selection from config
    if (empty($configuration['selection'])) {
      return;
    }

    // range
    $from = !empty($configuration['selection']['from'])
      ? (int) $configuration['selection']['from']
      : 0;
    $to = !empty($configuration['selection']['to'])
      ? (int) $configuration['selection']['to']
      : 99999999;
    $query->condition('n.nid', [$from, $to], 'BETWEEN');

    // live only?
    if (!empty($configuration['selection']['live'])) {
      $query->condition('n.status', 1);
    }
  }

  /**
   * Add left join to supplied node query.
   * @param $query - by reference
   * @param $fieldname
   * @param $alias
   * @param null $delta
   * @return void
   */
  public static function nodeFieldLeftJoin(SelectInterface &$query, $fieldname, $alias, $delta = NULL) {
    $join = "n.nid = ${alias}.entity_id AND ${alias}.entity_type = :type AND ${alias}.bundle = n.type AND ${alias}.deleted = 0";
    $join .= isset($delta) ? " AND ${alias}.delta = " . (int) $delta : '';
    //
    $query->leftjoin('field_data_' . $fieldname, $alias, $join, [
      ':type' => 'node',
    ]);
  }

}

