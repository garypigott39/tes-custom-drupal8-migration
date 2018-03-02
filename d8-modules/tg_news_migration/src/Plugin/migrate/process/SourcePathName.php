<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generates a full source path name for the supplied filename.
 *
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: source_path_name
 *     folder: public://  # default (optional) folder
 *     mapfolder:
 *       fieldname: myfield
 *       values:
 *         ... array of destination mapping pairs
 *     urldecode: true
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "source_path_name"
 * )
 */
class SourcePathName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = trim($value);
    if (empty($value)) {
      return NULL;
    }
    $value = !empty($this->configuration['urldecode']) ? urldecode($value) : $value;

    $filename = ltrim($value, '/');

    return $this->mapFolderName($row) . $filename;
  }

  /**
   * Map folder name to target.
   * @param \Drupal\migrate\Row $row
   * @throws \Drupal\migrate\MigrateException
   * @return string
   */
  private function mapFolderName(Row $row) {
    $folder = isset($this->configuration['folder']) ? $this->configuration['folder'] : '';
    if (isset($this->configuration['mapfolder'])) {
      $map = $this->configuration['mapfolder'];
      if (!isset($map['fieldname'])) {
        throw new MigrateException('mapfolder:fieldname is empty');
      }
      if (!isset($map['values'])) {
        throw new MigrateException('mapfolder:values is empty');
      }
      $fieldname = $map['fieldname'];
      if ($row->hasSourceProperty($fieldname)) {
        // populate mapping
        static $mapping;
        if (!isset($mapping)) {
          foreach ($map['values'] as $pair) {
            foreach ($pair as $key => $val) {
              $mapping[$key] = $val;
            }
          }
        }
        // map em
        $value = $row->getSourceProperty($fieldname);
        if (isset($value) && isset($mapping[$value])) {
          $folder = $mapping[$value];
        }
      }
    }

    //
    if (!empty($folder)) {
      $folder .= !preg_match('/\/$/', $folder) ? '/' : '';
    }
    return $folder;
  }
}
