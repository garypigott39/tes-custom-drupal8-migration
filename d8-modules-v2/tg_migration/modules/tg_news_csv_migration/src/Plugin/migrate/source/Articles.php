<?php

namespace Drupal\tg_news_csv_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\CSV;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Row;

/**
 * Extends our custom CSV class - for articles.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_csv_articles
 *   # @see \Drupal\tg_migration\Plugin\migrate\source\CSV
 * @endcode
 *
 * @MigrateSource(
 *   id = "tg_csv_articles"
 * )
 */
class Articles extends CSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // lets be pragmatic, this is the simplest way to do this....

    // concatenate arrays
    $tags = $this->getTagField($row, 'tags');
    $tags = array_merge($tags, $this->getTagField($row, 'category'));
    $tags = array_merge($tags, $this->getTagField($row, 'section'));
    $tags = array_filter($tags);  // remove nulls

    // term names may have embedded commas, hence...
    $row->setSourceProperty('tags', Utils::arrayToCSV($tags));

    return parent::prepareRow($row);
  }

  /**
   * Get tag field as an array.
   * @param $row
   * @param $fieldname
   * @return array
   */
  private function getTagField(Row $row, $fieldname) {
    $ret = $row->hasSourceProperty($fieldname)
      ? explode(', ', $row->getSourceProperty($fieldname))
      : [];
    return $ret;
  }
}
