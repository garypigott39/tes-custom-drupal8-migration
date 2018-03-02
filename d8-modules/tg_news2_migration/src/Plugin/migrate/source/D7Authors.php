<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\source;

use Drupal\tg_news2_migration\Plugin\migrate\D7Query;
use Drupal\tg_news2_migration\Plugin\migrate\D7Utils;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Plugin\migrate\source\EmptySource;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Drupal 7 authors from database.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_authors
 *
 * @MigrateSource(
 *   id = "d7_authors",
 * )
 */
class D7Authors extends SQLBase {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!D7Utils::checkSourceDatabase($this->getDatabase())) {
      throw new RequirementsException('Unable to connect to source database, or tables missing');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->addField('body', 'body_value', 'body');
    $query->addField('photo', 'field_byline_photo_fid', 'photo_fid');

    // rest of the selection & joins
    D7Query::nodeQuery($query, 'byline', $this->configuration);
    D7Query::nodeFieldLeftJoin($query, 'field_byline_photo', 'photo', $delta = 0);

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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $title = $row->getSourceProperty('title');
    if (empty($title)) {
      return FALSE;
    }

    $body = $row->getSourceProperty('body');
    if (isset($body)) {
      $body = strip_tags($body);
      $row->setSourceProperty('body', $body);
    }

    $photo_fid = $row->getSourceProperty('photo_fid');
    if (!empty($photo_fid)) {
      $image_alt = $title . ' picture';
      $row->setSourceProperty('image_alt', $image_alt);
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
