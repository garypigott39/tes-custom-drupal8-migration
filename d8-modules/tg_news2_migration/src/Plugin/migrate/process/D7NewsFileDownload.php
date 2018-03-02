<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\process;

use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use GuzzleHttp\Client;

/**
 * Drupal 7 file download.
 * @see Core Download process plugin for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   plugin: d7_news_file_download
 *   source:
 *     - source_url
 *     - destination_uri
 *   guzzle:
 *     # ability to override guzzle options
 *     timeout: 60  # default is 30
 *
 * @MigrateProcessPlugin(
 *   id = "d7_news_file_download"
 * )
 */
class D7NewsFileDownload extends Download {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, FileSystemInterface $file_system, Client $http_client) {
    if (isset($configuration['guzzle']) && is_array($configuration['guzzle'])) {
      foreach ($configuration['guzzle'] as $option => $value) {
        // overwrite any existing config option
        $configuration['guzzle_options'][$option] = $value;
      }
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition, $file_system, $http_client);
  }

}
