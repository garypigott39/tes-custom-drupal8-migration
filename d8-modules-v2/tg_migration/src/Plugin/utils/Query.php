<?php

namespace Drupal\tg_migration\Plugin\utils;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Query utilities for migration - from Source database.
 */
class Query {

  /**
   * Get managed file details.
   * @param $database
   * @param $fid
   * @return object | bool
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
   * Get node "alias" - url path.
   * @param $database
   * @param $nid
   * @return string | null
   */
  public static function getNodeAlias(Connection $database, $nid) {
    $query = $database->select('url_alias', 'a');
    $query->addField('a', 'alias');
    $query->condition('a.source', 'node/' . $nid);
    if ($alias = $query->execute()->fetchField()) {
      return $alias;
    }
    return NULL;
  }

  /**
   * Get node files (file ids).
   * @param $database
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  public static function getNodeFiles(Connection $database, $nid, $fieldname, $bundle) {
    $ret = [];
    if (isset($database)) {
      $table = 'field_data_' . $fieldname;
      $fieldname = $fieldname . '_fid';
      //
      $query = $database->select($table, 'f');
      $query->addField('f', $fieldname, 'fid');
      $query->condition('f.entity_id', $nid);
      $query->condition('f.bundle', $bundle);
      $query->condition('f.entity_type', 'node');
      $query->condition('f.deleted', 0);
      $query->orderBy('f.delta');
      // loop...
      foreach ($query->execute() as $row) {
        $row = (object) $row;  // prefer objects
        $ret[$row->fid] = $row->fid;  // ensures uniqueness!!
      }
    }
    return $ret;
  }

  /**
   * Get node "tags" (taxonomy terms) - returned array is keyed by term id & includes the term name.
   * @param $database
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  public static function getNodeTags($database, $nid, $fieldname, $bundle) {
    $ret = [];
    if (isset($database)) {
      $table = 'field_data_' . $fieldname;
      $fieldname = $fieldname . '_tid';
      //
      $query = $database->select($table, 'tag');
      $query->join('taxonomy_term_data', 'taxonomy', "tag.{$fieldname} = taxonomy.tid");
      $query->addField('tag', $fieldname, 'tid');
      $query->addField('taxonomy', 'name');
      $query->condition('tag.entity_id', $nid);
      $query->condition('tag.bundle', $bundle);
      $query->condition('tag.entity_type', 'node');
      $query->condition('tag.deleted', 0);
      $query->orderBy('tag.delta');
      // loop...
      foreach ($query->execute() as $row) {
        $row = (object) $row;
        $ret[$row->tid] = $row->name;
      }
    }
    return $ret;
  }

  /**
   * Get node "terms" (entities).
   * @param $database
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  public static function getNodeTerms($database, $nid, $fieldname, $bundle) {
    $ret = [];
    if (isset($database)) {
      $table = 'field_data_' . $fieldname;
      $fieldname = $fieldname . '_target_id';
      //
      $query = $database->select($table, 'term');
      $query->addField('term', $fieldname, 'nid');
      $query->condition('term.entity_id', $nid);
      $query->condition('term.bundle', $bundle);
      $query->condition('term.entity_type', 'node');
      $query->condition('term.deleted', 0);
      $query->orderBy('term.delta');
      // loop...
      foreach ($query->execute() as $row) {
        $row = (object) $row;
        $ret[$row->nid] = $row->nid;
      }
    }
    return $ret;
  }

  /**
   * Initialise node query.
   * @param $query - by reference
   * @param $node_type
   * @param $selection
   * @return void
   */
  public static function nodeQuery(SelectInterface &$query, $node_type, array $selection = []) {
    $query->condition('n.type', $node_type);

    $query->leftjoin('field_data_body', 'body', 'n.nid = body.entity_id AND body.entity_type = :type AND body.bundle = n.type AND body.deleted = 0 AND body.delta = 0', [
        ':type' => 'node',
      ]);

    // additional selection from config
    if (empty($selection)) {
      return;
    }

    // range
    $from = !empty($selection['from']) ? (int) $selection['from'] : 0;
    $to = !empty($selection['to']) ? (int) $selection['to'] : 99999999;
    $query->condition('n.nid', [$from, $to], 'BETWEEN');

    // entity queue?
    if (!empty($selection['entity_queue'])) {
      self::nodeQueueJoin($query, 'n.nid', $selection['entity_queue']);
    }

    // live only?
    if (isset($selection['status'])) {
      $query->condition('n.status', (int) $selection['status']);
    }
  }

  /**
   * Add inner join to supplied node query.
   * @param $query - by reference
   * @param $fieldname
   * @param $alias
   * @param null $delta
   * @param $funcname
   * @return void
   */
  public static function nodeFieldJoin(SelectInterface &$query, $fieldname, $alias, $delta = NULL, $func = 'join') {
    $condition = "n.nid = {$alias}.entity_id AND {$alias}.entity_type = :type AND {$alias}.bundle = n.type AND {$alias}.deleted = 0";
    $condition .= isset($delta) ? " AND {$alias}.delta = " . (int) $delta : '';
    //
    $func = in_array($func, ['join', 'leftjoin']) ? $func : 'join';
    $query->$func('field_data_' . $fieldname, $alias, $condition, [
      ':type' => 'node',
    ]);
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
    self::nodeFieldJoin($query, $fieldname, $alias, $delta, 'leftjoin');
  }

  /**
   * Add node entity queue join.
   * @param $query - by reference
   * @param $nid_fieldname
   * @param $queue_name
   * @return void
   */
  public static function nodeQueueJoin(SelectInterface &$query, $nid_fieldname, $queue_name) {
    if (!empty($queue_name)) {
      $condition = $nid_fieldname . ' = eq.eq_node_target_id AND eq.entity_type = :queue_entity_type AND eq.bundle = :queue_bundle AND eq.deleted = 0';
      $args = [
        ':queue_entity_type' => 'entityqueue_subqueue',
        ':queue_bundle' => $queue_name,
      ];
      $query->join('field_data_eq_node', 'eq', $condition, $args);
    }
  }

  /**
   * Check if named table exists.
   * @param $database
   * @param $table
   * @return bool
   */
  public static function tableExists(Connection $database, $table) {
    return $database->schema()->tableExists($table);
  }

}
