<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\file\Plugin\migrate\source\d7\File as D7File;

use Drupal\migrate\Row;


/**
 * Drupal 7 source hero image files from database. We are choosing between
 * hero and teasers - so we make use of the NOT IN selection for hero images
 * but for teasers we get everything using the basic file class.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_sql_hero_files
 *
 * @MigrateSource(
 *   id = "tg_sql_hero_files",
 * )
 */
class HeroFiles extends D7File {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!Utils::checkSourceDatabase($this->database)) {
      $msg = $this->errorMessage('Unable to connect to source database, or tables missing');
      throw new RequirementsException($msg);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->addField('n', 'nid');
    $query->addField('hero', 'field_news_article_images_fid');

    // rest of the selection & joins
    $selection = isset($this->configuration['selection']) ? $this->configuration['selection'] : [];

    Query::nodeQuery($query, 'news_article', $selection);
    Query::nodeFieldJoin($query, 'field_news_article_images', 'hero', $delta = 0);

    //------------------------------------
    // exclude it there is a teaser image
    //------------------------------------
    $subquery = $this->select('node', 'n');
    $subquery->addField('n', 'nid');
    Query::nodeFieldJoin($subquery, 'field_news_article_teaser_image', 'teaser', $delta = 0);

    $query->condition('n.nid', $subquery, 'NOT IN');

    $query->orderBy('n.nid');

    //$query->distinct();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // lets be pragmatic, this stuff is easiest done here rather than complex YML etc... even if its possible
    $fid = $row->getSourceProperty('field_news_article_images_fid');
    if (empty($fid) || !$file = Query::getFileObject($this->database, $fid)) {
      return FALSE;
    }
    // set source properties for the file
    foreach ($file as $fieldname => $value) {
      $row->setSourceProperty($fieldname, $value);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
        'nid' => t('Node Id'),
        'field_news_article_images_fid' => t('Hero Image File Id'),
      ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    // when building the source query migrate uses this is a LeftJoin, so the naming (& alias) is relevant
    // -> e.g. LEFT OUTER JOIN {migrate_map_news_sql_teasers} map ON {nid} = map.sourceid1
    $ids['field_news_article_images_fid'] = [
      'type' => 'integer',
      'alias' => 'hero',
    ];
    return $ids;
  }

}

