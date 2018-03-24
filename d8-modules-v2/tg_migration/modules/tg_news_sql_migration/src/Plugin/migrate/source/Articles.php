<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\SQL;
use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Row;

/**
 * Drupal 7 articles source from database.
 *
 * @MigrateSource(
 *   id = "tg_sql_articles"
 * )
 */
class Articles extends SQL {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->addField('body', 'body_value', 'body');
    $query->addField('standfirst', 'field_news_article_standfirst_value', 'standfirst');
    $query->addField('post_script', 'field_news_article_post_script_value', 'post_script');
    $query->addField('publication_date', 'field_publication_date_value', 'publication');
    $query->addField('author', 'field_news_article_byline_target_id', 'authors');
    $query->addField('teaser', 'field_news_article_teaser_image_fid', 'teaser');
    $query->addField('hero', 'field_news_article_images_fid', 'images');

    // rest of selection & joins
    Query::nodeQuery($query, 'news_article', $this->configuration['selection']);

    // teaser & hero image fields
    Query::nodeFieldLeftJoin($query, 'field_news_article_teaser_image', 'teaser', $delta = 0);
    Query::nodeFieldLeftJoin($query, 'field_news_article_images', 'hero', $delta = 0);

    // standfirst
    Query::nodeFieldLeftJoin($query, 'field_news_article_standfirst', 'standfirst', $delta = 0);

    // post script
    Query::nodeFieldLeftJoin($query, 'field_news_article_post_script', 'post_script', $delta = 0);

    // publication date
    Query::nodeFieldLeftJoin($query, 'field_publication_date', 'publication_date', $delta = 0);

    // author
    Query::nodeFieldLeftJoin($query, 'field_news_article_byline', 'author', $delta = 0);

    // tags, attachments, related content, etc is in prepareRow()

    $query->orderBy('n.nid');

    //$query->distinct();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');

    // 1. get attachments
    if ($attached_files = Query::getNodeFiles($this->database, $nid, 'field_news_article_files', 'news_article')) {
      $row->setSourceProperty('files', implode(', ', $attached_files));
    }

    // 2. get tags
    $tags = Query::getNodeTags($this->database, $nid, 'field_news_article_tags', 'news_article');
    $tags += Query::getNodeTags($this->database, $nid, 'field_news_category', 'news_article');
    $tags += Query::getNodeTags($this->database, $nid, 'field_news_section', 'news_article');
    $tags = array_filter($tags);  // remove nulls

    // term names may have embedded commas, hence...
    $row->setSourceProperty('tags', Utils::arrayToCSV($tags));

    // 3. Related content
    if ($related = Query::getNodeTerms($this->database, $nid, 'field_news_article_related_conte', 'news_article')) {
      $row->setSourceProperty('related', implode(', ', $related));
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid'         => t('Source Node id'),
      'title'       => t('Title'),
      'body'        => t('Body'),
      'standfirst'  => t('Standfirst'),
      'post_script' => t('Post script - not used'),
      'publication' => t('Publication date'),  //@todo: use created date instead
      'authors'     => t('Authors (only 1)'),
      'teaser'      => t('Teaser Image'),
      'images'      => t('Hero Image'),
      'files'       => t('Attached files - not used'),
      'tags'        => t('Tags'),
      'related'     => t('Related content'),
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