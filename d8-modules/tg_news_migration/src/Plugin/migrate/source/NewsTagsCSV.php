<?php

namespace Drupal\tg_news_migration\Plugin\migrate\source;

use Drupal\tg_news_migration\Plugin\migrate\HelperUtils;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id = "news_tags_csv"
 * )
 */
class NewsTagsCSV extends TesNewsCSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $title = trim( $row->getSourceProperty('title') );

    # change value perhaps
    $title = HelperUtils::tagsXref($title);

    // change term name?
    $vocab = $row->getSourceProperty('vocab');
    if ($vocab != 'News Tags') {
      $title = $vocab . ': ' . $title;
    }
    $row->setSourceProperty('title', $title);

    // Skip if term already in Vocab
    if ($this->newsTagExists($title)) {
      return FALSE;
    }

    return parent::prepareRow($row);
  }

  /**
   * Checks is tag name already exists.
   * @param $title
   * @throws \Drupal\migrate\MigrateException
   * @return bool
   */
  private function newsTagExists($title) {
    //$rowno = $this->file->key();
    $process = $this->migration->getProcess();
    $dest = $this->migration->getDestinationConfiguration();

    $vid = isset($process['vid'][0]['default_value'])
      ? $process['vid'][0]['default_value']
      : NULL;
    if (!isset($vid)) {
      $vid = isset($dest['default_bundle'])
        ? $dest['default_bundle']
        : NULL;
    }
    if (!isset($vid)) {
      throw new MigrateException('Unable to determine vocabulary id');
    }

    // now see if term already exists
    if ($term = taxonomy_term_load_multiple_by_name($title, $vid)) {
      return TRUE;
    }

    return FALSE;
  }
}
