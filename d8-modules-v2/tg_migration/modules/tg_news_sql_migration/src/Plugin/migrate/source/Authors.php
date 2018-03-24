<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\SQL;
use Drupal\tg_migration\Plugin\utils\Query;

/**
 * Drupal 7 authors source from database.
 *
 * @MigrateSource(
 *   id = "tg_sql_authors"
 * )
 */
class Authors extends SQL {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->addField('body', 'body_value', 'body');
    $query->addField('photo', 'field_byline_photo_fid', 'photo_fid');
    $query->addField('twitter', 'field_byline_twitter_name_value', 'twitter');

    // rest of the selection & joins
    Query::nodeQuery($query, 'byline');
    Query::nodeFieldLeftJoin($query, 'field_byline_photo', 'photo', $delta = 0);
    Query::nodeFieldLeftJoin($query, 'field_byline_twitter_name', 'twitter', $delta = 0);

    $this->baseSelection($query, 'field_data_field_news_article_byline', 'field_news_article_byline_target_id');

    $query->orderBy('n.nid');

    $query->distinct();  // we do need the DISTINCT here because of the "baseSelection" and hence duplicates!

    // $sql = (string) $query;  # if ever want to debug SQL...

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => t('Node Id'),
      'title' => t('Author name'),
      'body' => t('Author description'),
      'photo_fid' => t('Author Image File Id'),
      'twitter' => t('Twitter Handle'),
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
