<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * @see Core Download process plugin for more details.
 *
 * @MigrateProcessPlugin(
 *   id = "news_file_download"
 * )
 */
class NewsFileDownload extends Download {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    if (!empty($this->configuration['validate_source_uri'])) {
      @list($source, $destination) = $value;
      // so this is just a wrapper for the parent transform, the only difference is that we will try to check for
      // existance of source URL before any of the other gubbins
      if ($row->isStub() || empty($source)) {
        return NULL;
      }
      // this does mean we end up running the "GET" twice (@see parent::transform)
      // -> but we dont get left with loads of crap files...
      try {
        $res = $this->httpClient->get($source);
      } catch (\Exception $e) {
        watchdog_exception('tg_news_migration', $e);
        // throw it out!
        return NULL;
      }
    }
    return $destination = parent::transform($value, $migrate_executable, $row, $destination_property);
  }

}
