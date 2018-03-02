<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\source;

use Drupal\tg_news2_migration\Plugin\migrate\D7Query;

use Drupal\migrate\Row;

/**
 * Drupal 7 news teaser images from database.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_news_teasers
 *
 * @MigrateSource(
 *   id = "d7_news_teasers",
 * )
 */
class D7NewsTeasers extends D7NewsFiles {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->addField('n', 'nid');
    $query->addField('teaser', 'field_news_article_teaser_image_fid', 'teaser_fid');
    $query->addField('hero', 'field_news_article_images_fid', 'hero_fid');

    // rest of the selection & joins
    D7Query::nodeQuery($query, 'news_article', $this->configuration);
    D7Query::nodeFieldLeftJoin($query, 'field_news_article_teaser_image', 'teaser', $delta = 0);
    D7Query::nodeFieldLeftJoin($query, 'field_news_article_images', 'hero', $delta = 0);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => t('Node Id'),
      'teaser_fid' => t('Teaser Image File Id'),
      'hero_fid' => t('Hero Image File Id'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // choose between teaser & hero
    $fid = $row->getSourceProperty('teaser_fid');
    if (empty($fid)) {
      $fid = $row->getSourceProperty('hero_fid');
    }

    // valid file?
    if (empty($fid) || !$file = D7Query::getFileObject($this->getDatabase(), $fid)) {
      return FALSE;
    }

    // set source properties
    foreach ($file as $fieldname => $value) {
      $row->setSourceProperty($fieldname, $value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

}
