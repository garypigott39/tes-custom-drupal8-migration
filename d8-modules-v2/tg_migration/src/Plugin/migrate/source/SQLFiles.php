<?php
 
namespace Drupal\tg_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\file\Plugin\migrate\source\d7\File as D7File;

/**
 * Drupal 7 source files from database. Can be linked to selection entity ids if required
 * by optional entity_id range etc.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_sql_files
 *   source_sql:
 *     table: field_data_field_news_article_teaser_image
 *     field: field_news_article_teaser_image_fid
 *     bundle: 'news_article'  # default is all bundles
 *   selection:
 *     from: 100000    # parent RANGE node selection
 *     to:   200000
 *     status: 1
 *   delta: 0  # default is all
 * @endcode
 *
 * Also supports an "entity_queue" argument instead of the "selection" range.
 *
 * @MigrateSource(
 *   id = "tg_sql_files",
 * )
 */
class SQLFiles extends D7File {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!Utils::checkSourceDatabase($this->getDatabase())) {
      $msg = $this->errorMessage('Unable to connect to source database, or tables missing');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['source_sql'])) {
      $msg = $this->errorMessage('source_sql undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['source_sql']['table'])) {
      $msg = $this->errorMessage('source_sql:table undefined');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['source_sql']['field'])) {
      $msg = $this->errorMessage('source_sql:field undefined');
      throw new RequirementsException($msg);
    }
    elseif (isset($this->configuration['selection']) && !is_array($this->configuration['selection'])) {
      $msg = $this->errorMessage('selection must be an array');
      throw new RequirementsException($msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    // live files only
    $query->condition('f.status', 1);

    // add other conditions
    $joinwith = $this->getJoinConditions();

    // now, join to source table
    $query->join($this->configuration['source_sql']['table'], 'sourcetable', $joinwith['conditions'], $joinwith['args']);

    if (!empty($joinwith['entityqueue'])) {
      $query->join($joinwith['entityqueue']['table'], $joinwith['entityqueue']['alias'], $joinwith['entityqueue']['conditions'], $joinwith['entityqueue']['args']);
    }

    if (!empty($joinwith['node'])) {
      $query->join('node', $joinwith['node']['alias'], $joinwith['node']['conditions'], $joinwith['node']['args']);
    }

    $query->orderBy('f.fid');

    $query->distinct();

    return $query;
  }

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  protected function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->migration->id(), $msg, $args);
  }

  /**
   * Build array of join conditions.
   * @return array
   */
  private function getJoinConditions() {
    $sql = $this->configuration['source_sql'];

    $selection = isset($this->configuration['selection']) ? $this->configuration['selection'] : [];

    // build em
    $join = [
      'conditions' => 'f.fid = sourcetable.' . $sql['field'],
      'args' => [],
    ];

    // bundle?
    if (!empty($sql['bundle'])) {
      $join['conditions'] .= ' AND sourcetable.bundle = :bundle';
      $join['args'][':bundle'] = $sql['bundle'];
    }

    // delta?
    if (isset($sql['delta'])) {
      $join['conditions'] .= ' AND sourcetable.delta = :delta';
      $join['args'][':delta'] = $sql['delta'];
    }

    // range?
    if (isset($selection['from']) && (int) $selection['from'] > 0) {
      $join['conditions'] .= ' AND sourcetable.entity_id >= :from';
      $join['args'][':from'] = (int) $selection['from'];
    }
    if (isset($selection['to']) && (int) $selection['to'] > 0) {
      $join['conditions'] .= ' AND sourcetable.entity_id <= :to';
      $join['args'][':to'] = (int) $selection['to'];
    }

    // node status?
    if (isset($selection['status'])) {
      $join['node'] = [
        'alias' => 'n',
        'conditions' => 'sourcetable.entity_id = n.nid AND sourcetable.bundle = n.type AND n.status = :nodestatus',
        'args' => [
          ':nodestatus' => (int) $selection['status'],
        ]
      ];
    }

    // entity queue?
    if (isset($selection['entity_queue'])) {
      $join['entityqueue'] = [
        'table' => 'field_data_eq_node',
        'alias' => 'eq',
        'conditions' => 'sourcetable.entity_id = eq.eq_node_target_id AND eq.entity_type = :entity_type AND eq.bundle = :entityqueue_bundle AND eq.deleted = 0',
        'args' => [
          ':entity_type' => 'entityqueue_subqueue',
          ':entityqueue_bundle' => $selection['entity_queue'],
        ],
      ];
    }

    return $join;
  }

}
