<?php

namespace Drupal\tg_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\Core\Database\Query\SelectInterface;

/**
 * Custom class SQL extends SqlBase version.
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase;
 *
 * This is intended to be used as a base class.
 */
abstract class SQL extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!Utils::checkSourceDatabase($this->getDatabase())) {
      $msg = $this->errorMessage('Unable to connect to source database, or tables missing');
      throw new RequirementsException($msg);
    }
    elseif (!isset($this->configuration['selection']) || !is_array($this->configuration['selection'])) {
      $msg = $this->errorMessage('selection array undefined');
      throw new RequirementsException($msg);
    }
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
   * Add in base "news article" selection, if applicable.
   * @param $query - by reference
   * @param $table
   * @param $fieldname
   * @param $bundle - source bundle
   * @return void
   */
  protected function baseSelection(SelectInterface &$query, $table, $fieldname, $bundle = 'news_article') {
    if (!empty($this->configuration['selection'])) {
      $selection = $this->configuration['selection'];
      $from = !empty($selection['from']) ? (int) $selection['from'] : 0;
      $to = !empty($selection['to']) ? (int) $selection['to'] : 99999999;
      //
      $condition = "n.nid = article.{$fieldname} AND article.deleted = 0 AND article.entity_type = :type AND article.bundle = :bundle AND article.deleted = 0";
      $args = [
        ':type' => 'node',
        ':bundle' => $bundle,
      ];
      //
      if ($from > 0 || $to < 99999999) {
        $condition .= ' AND article.entity_id BETWEEN :from AND :to';
        $args += [
          ':from' => $from,
          ':to' => $to,
        ];
        $query->join($table, 'article', $condition, $args);
      }
      elseif (!empty($selection['entity_queue'])) {
        $query->join($table, 'article', $condition, $args);
        //
        Query::nodeQueueJoin($query, 'article.entity_id', $selection['entity_queue']);
      }
    }
  }

}
