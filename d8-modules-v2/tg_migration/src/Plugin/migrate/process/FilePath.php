<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a destination file pathname. Can also be used to generate a source
 * file pathname as long as its relative uris (i.e. doesnt contain a domain or
 * public:// etc).
 * @see Core ProcessPluginBase for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * Example:
 *
 * @code
 * process:
 *   file_dest:
 *     plugin: tg_file_path
 *     source: filename
 *     folder: 'public://'
 *     niceify: true  # default false
 *     shorten: 60    # shorten long filenames to 60 characters, default is name unchanged
 *     skip: row  # default is process
 *     message: 'Source path is undefined'
 * @endcode
 *
 * Alternatively folder can be used to provide mapping rules for file destination, e.g.
 *
 *    folder:
 *      map_field: image_type
 *      map_values:
 *        'teaser image': 'public://news-images'
 *        'hero image': 'public://news-images'
 *        'attachment': 'public://news-attachments'
 *      default: 'public://'
 *      urldecode: true
 *
 * @MigrateProcessPlugin(
 *   id = "tg_file_path"
 * )
 */
class FilePath extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    // some default config
    if (!isset($this->configuration['skip'])) {
      $this->configuration['skip'] = 'process';
    }
    if (!isset($this->configuration['message'])) {
      $this->configuration['message'] = '';
    }

    // handle the value
    if (isset($value)) {
      $folder = is_array($this->configuration['folder'])
        ? $this->mapFolderName($row)
        : $this->configuration['folder'];
      $value = Utils::buildPathName($folder, $value);
    }
    elseif ($this->configuration['skip'] == 'row') {
      $msg = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      $msg = $this->errorMessage($msg);
      throw new MigrateSkipRowException($msg);
    }
    else {
      throw new MigrateSkipProcessException();
    }

    // niceify?
    if (!empty($this->configuration['niceify'])) {
      list($path, $filename) = Utils::filePathAndName($value);
      $filename = Utils::niceFileName($filename);
      $value = Utils::buildPathName($path, $filename);
    }

    // shorten?
    if (!empty($this->configuration['shorten'])) {
      $value = Utils::shortFileName($value, (int) $this->configuration['shorten']);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    if (!isset($this->configuration['folder'])) {
      throw new RequirementsException('folder undefined');
    }
    elseif (is_array($this->configuration['folder'])) {
      if (!isset($this->configuration['folder']['map_field'])) {
        throw new RequirementsException('folder:map_field undefined');
      }
      elseif (!isset($this->configuration['folder']['map_values'])) {
        throw new RequirementsException('folder:map_values undefined');
      }
      elseif (!isset($this->configuration['folder']['default'])) {
        throw new RequirementsException('folder:default undefined');
      }
    }
  }

  /**
   * Map folder name.
   * @param $row
   * @return string
   */
  private function mapFolderName(Row $row) {
    $folder = $this->configuration['folder']['default'];
    //
    $fieldname = $this->configuration['folder']['map_field'];
    if ($row->hasSourceProperty($fieldname)) {
      // populate mapping rules
      static $mapping;
      if (!isset($mapping)) {
        foreach ($this->configuration['folder']['map_values'] as $key => $value) {
          $mapping[$key] = $value;
        }
      }
      //
      $fieldvalue = $row->getSourceProperty($fieldname);
      if (isset($fieldvalue) && isset($mapping[$fieldvalue])) {
        $folder = $mapping[$fieldvalue];
      }
    }
    return $folder;
  }

}
