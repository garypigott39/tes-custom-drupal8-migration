<?php

namespace Drupal\tg_news_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * @MigrateSource(
 *   id = "news_authors_csv"
 * )
 */
class NewsAuthorsCSV extends TesNewsCSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // simple replace " and " with "&"
    $title = $row->getSourceProperty('title');
    $title = !empty($title)
      ? preg_replace('/ and /i', ' & ', $title)
      : 'Undefined';
    $row->setSourceProperty('title', $title);

    // remove any tags
    $body = $row->getSourceProperty('body');
    if (!empty($body)) {
      $body = strip_tags($body);
      $row->setSourceProperty('body', $body);
    }

    // and add an "image alt"
    $photo = urldecode( $row->getSourceProperty('photo') );
    if (!empty($photo)) {
      $image_alt = $title . ' picture';
      $row->setSourceProperty('image_alt', $image_alt);

      // plus remove leading slash from name so it emulates what we did in the images bit
      $photo = ltrim(urldecode($row->getSourceProperty('photo')));
      $row->setSourceProperty('photo', $photo);
    }

    return parent::prepareRow($row);
  }

}
