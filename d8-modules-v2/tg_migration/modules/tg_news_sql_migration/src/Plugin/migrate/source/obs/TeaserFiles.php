<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\utils\Query;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\file\Plugin\migrate\source\d7\File as D7File;

use Drupal\migrate\Row;


/**
 * Drupal 7 source teaser image files from database. Unlike the file migration
 * we need to choose between image fields - teaser or hero, hence we extend 
 * the Base class and add additional selection.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_sql_teaser_files
 *
 * @MigrateSource(
 *   id = "tg_sql_teaser_files",
 * )
 */
class TeaserFiles extends D7File {

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
    $query->addField('teaser', 'field_news_article_teaser_image_fid', 'teaser_fid');
    $query->addField('hero', 'field_news_article_images_fid', 'hero_fid');

    // rest of the selection & joins
    $selection = isset($this->configuration['selection']) ? $this->configuration['selection'] : [];

    Query::nodeQuery($query, 'news_article', $selection);
    Query::nodeFieldLeftJoin($query, 'field_news_article_teaser_image', 'teaser', $delta = 0);
    Query::nodeFieldLeftJoin($query, 'field_news_article_images', 'hero', $delta = 0);

    $query->orderBy('n.nid');

    $query->distinct();

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // lets be pragmatic, this stuff is easiest done here rather than complex YML etc... even if its possible
    if (!$row->isStub()) {
      // choose between teaser & hero
      $fid = $row->getSourceProperty('teaser_fid');
      if (empty($fid)) {
        $fid = $row->getSourceProperty('hero_fid');
      }
      // valid file?
      if (empty($fid) || !$file = Query::getFileObject($this->database, $fid)) {
        return FALSE;
      }
      // set source properties for the file
      foreach ($file as $fieldname => $value) {
        $row->setSourceProperty($fieldname, $value);
      }
    }

    return parent::prepareRow($row);
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
  public function getIds() {
    // when building the source query migrate uses this is a LeftJoin, so the naming (& alias) is relevant
    // -> e.g. LEFT OUTER JOIN {migrate_map_news_sql_teasers} map ON {nid} = map.sourceid1
    $ids['nid'] = [
      'type' => 'integer',
      'alias' => 'n',
    ];
    return $ids;
  }

}

