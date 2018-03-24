<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\SQL;
use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Row;

/**
 * Drupal 7 author redirects source from database.
 *
 * @MigrateSource(
 *   id = "tg_sql_author_redirects"
 * )
 */
class AuthorRedirects extends SQL {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->addField('n','nid');

    // rest of selection & joins
    Query::nodeQuery($query, 'byline');

    $this->baseSelection($query, 'field_data_field_news_article_byline', 'field_news_article_byline_target_id');

    $query->orderBy('n.nid');

    $query->distinct();  // we do need the DISTINCT here because of the "baseSelection" and hence duplicates!

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    if ($alias = Query::getNodeAlias($this->database, $nid)) {
      $row->setSourceProperty('uri', $alias);
    }
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => t('Source Node id'),
      'uri' => t('Uri - path alias'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}
