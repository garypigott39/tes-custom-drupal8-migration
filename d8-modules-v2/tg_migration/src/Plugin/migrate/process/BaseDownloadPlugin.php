<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Entity\File;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\Download;
use Drupal\migrate\Row;
use GuzzleHttp\Client;

/**
 * Wrapper for \Download.
 * @see Core Download plugin for more details.
 * 
 * Exposes ability to set "guzzle" options like timeout etc and a "clear_stubs" option.
 *
 * Also exposes a local_file argument allowing local file move, copy, or ignore (pass through url).
 *
 * Example:
 *
 * @code
 * process:
 *   plugin: [plugin-name]
 *   source:
 *     - source_url
 *     - destination_uri
 *   guzzle:
 *     # ability to override guzzle options
 *     timeout: 60  # default is 30
 *   clear_stubs: true  # delete obsolete stub entry
 * @endcode
 *
 * With the local file option.
 *
 * @code
 * process:
 *   plugin: [plugin-name]
 *   source:
 *     - source_filepath
 *     - destination_uri
 *   local_file: 'move'
 *   valid_path_regex: '#^/home/gary/IMAGES#'  # only applies to move/copy options
 *   clear_stubs: true  # delete obsolete stub entry
 * @endcode
 */
abstract class BaseDownloadPlugin extends Download {

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

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->initConfig($row);

    $this->checkRequirements($value);

    list($source, $destination) = $value;

    switch (TRUE) {
      case !isset($this->configuration['local_file']):
        $value = parent::transform($value, $migrate_executable, $row, $destination_property);
        break;
      case $this->configuration['local_file'] == 'ignore':
        // just pass through the destination
        $value = $destination;
        break;
      case !preg_match($this->configuration['valid_path_regex'], $source):
        $msg = $this->errorMessage('Source file path @source doesnt match regex', [
          '@source' => $source,
        ]);
        throw new RequirementsException($msg);
      case Utils::fileExists($destination):
        $value = $destination;
        break;
      case $this->configuration['local_file'] == 'move':
        // move the file from source path to destination
        $value = file_unmanaged_move($source, $destination, $replace = FILE_EXISTS_REPLACE);
        break;
      case $this->configuration['local_file'] == 'copy':
        // copy the file from source path to destination
        $value = file_unmanaged_copy($source, $destination, $replace = FILE_EXISTS_REPLACE);
        break;
      default:
    }

    // check source & target
    if ($value) {
      // so as we're dealing with files the ID may be the same but the destination URI will be different so we
      // may need to remove any "stub" files...
      if (!$row->isStub() && !empty($this->configuration['clear_stubs'])) {
        $map = $row->getIdMap();
        $fid = !empty($map['destid1']) ? (int) $map['destid1'] : NULL;
        $this->deleteStubFile($fid, $value);
      }
    }

    return $value;
  }

  /**
   * Expose a checkRequirements method on transformation.
   * @throws \Drupal\migrate\Exception\RequirementsException;
   */
  public function checkRequirements($value) {
    if (!is_array($value) || count($value) != 2) {
      $msg = $this->errorMessage('local_file invalid argument supplied');
      throw new RequirementsException($msg);
    }
    elseif (isset($this->configuration['local_file'])) {
      if (!in_array($this->configuration['local_file'], ['move', 'copy', 'ignore'])) {
        $msg = $this->errorMessage('local_file invalid argument supplied');
        throw new RequirementsException($msg);
      }
      elseif ($this->configuration['local_file'] == 'ignore') {
        // do nothing
      }
      elseif (!isset($this->configuration['valid_path_regex']) || !Utils::isValidRegex($this->configuration['valid_path_regex'])) {
        $msg = $this->errorMessage('valid_path_regex undefined/invalid');
        throw new RequirementsException($msg);
      }
      //@todo: ensure that source is a valid file path
    }
  }

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  protected function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->pluginId, $msg, $args);
  }

  /**
   * Initialise process plugin config. Basically a (much) simplified version of the
   * get functionality...
   * @param \Drupal\migrate\Row $row
   * @return void
   */
  protected function initConfig(Row $row) {
    Utils::initProcessConfig($this->configuration, $row);
  }

  // Private methods

  /**
   * Delete (old) stub file if any.
   * @param $fid
   * @param $value
   */
  private function deleteStubFile($fid, $value) {
    switch (TRUE) {
      case empty($fid):
      case !$file = File::load($fid):
      case !$uri = $file->getFileUri():
      case $uri == $value:
      case (int) $file->getSize() > 0:
        break;
      default:
        Utils::deleteFile($uri);
    }
  }
}
