<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\tg_news_migration\Plugin\migrate\HelperUtils;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generates a nice file name for the supplied filename.
 *
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: nice_file_name
 *     basename: true     # default is false, if true just get base name
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "nice_file_name"
 * )
 */
class NiceFileName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = $value = trim($value);
    if (empty($value)) {
      return NULL;
    }

    list($pathname, $filename) = HelperUtils::getPathAndFileName($value);

    // just in case there is an embedded "?"
    $filename = HelperUtils::deTok($filename);
    $filename = HelperUtils::niceFileName($filename);

    // glue the bits back together?
    if (empty($this->configuration['basename'])) {
      $filename = !empty($pathname) ? $pathname . '/' . $filename : $filename;
    }

    return $filename;
  }
}
