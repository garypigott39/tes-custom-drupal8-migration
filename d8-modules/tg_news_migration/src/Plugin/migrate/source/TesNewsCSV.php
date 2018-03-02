<?php

namespace Drupal\tg_news_migration\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\EmptySource;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV as BaseCSV;

abstract class TesNewsCSV extends BaseCSV {

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    // Migration falls over if file doesnt exist, so here we give an error instead!
    if (!file_exists($this->configuration['path'])) {
      $msg = t('Unable to get source data CSV file "%path".', [
        '%path' => $this->configuration['path']
        ]
      );
      drupal_set_message($msg, 'error', TRUE);
      return EmptySource::initializeIterator();
    }
    return parent::initializeIterator();
  }

}
