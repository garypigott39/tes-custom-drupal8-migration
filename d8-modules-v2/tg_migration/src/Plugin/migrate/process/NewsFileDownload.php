<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

/**
 * News file download.
 * @see Core Download plugin for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   plugin: tg_file_download
 *   source:
 *     - source_url
 *     - destination_uri
 *   guzzle:
 *     # ability to override guzzle options
 *     timeout: 60  # default is 30
 *   clear_stubs: true  # delete obsolete stub entry
 * @endcode
 *
 * Note if dealing with local files then used the "local_file" option with either copy/move/ignore. The ignore
 * argument will simply pass through the uri.
 *
 * @MigrateProcessPlugin(
 *   id = "tg_file_download"
 * )
 */
class NewsFileDownload extends BaseDownloadPlugin { }
