<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\source;

use Drupal\tg_news2_migration\Plugin\migrate\D7Utils;

use Drupal\migrate\Row;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\file\Plugin\migrate\source\d7\File as D7File;

/**
 * Drupal 7 source files from database. Note that unlike the "node" extracts like
 * teaser & news article, this does NOT use the node selection criteria instead it
 * pulls over all files from the named field.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_news_files
 *   source_sql:
 *     table: field_data_field_byline_photo
 *     field: field_byline_photo_fid
 *     live: true
 *
 * @MigrateSource(
 *   id = "d7_news_files",
 * )
 */
class D7NewsFiles extends D7File {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!D7Utils::checkSourceDatabase($this->getDatabase())) {
      throw new RequirementsException('Unable to connect to source database, or tables missing');
    }
    elseif (!isset($this->configuration['folders'])) {
      throw new RequirementsException('Config - folders undefined');
    }
    elseif (!isset($this->configuration['folders']['source_domain'])) {
      throw new RequirementsException('Config - folders:source_domain undefined');
    }
    elseif (!isset($this->configuration['folders']['source_public_folder_uri'])) {
      throw new RequirementsException('Config - folders:source_public_folder_uri undefined');
    }
    elseif (!isset($this->configuration['folders']['target_folder'])) {
      throw new RequirementsException('Config - folders:target_folder undefined');
    }
    elseif (isset($this->configuration['source_sql'])) {
      // optional selection criteria, which if supplied must have certain bits
      $sql = $this->configuration['source_sql'];
      if (empty($sql['table'])) {
        throw new RequirementsException('Config - source_sql:table is empty');
      }
      elseif (empty($sql['field'])) {
        throw new RequirementsException('Config - source_sql:field is empty');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    if (isset($this->configuration['source_sql'])) {
      $sql = $this->configuration['source_sql'];
      if (!empty($sql['live'])) {
        $query->condition('f.status', 1);
      }
      $join = 'f.fid = sourcetable.' . $sql['field'];
      $query->join($sql['table'], 'sourcetable', $join);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
        'destination_file_path' => t('(Custom field) Destination file and path'),
      ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $uri = $row->getSourceProperty('uri');
    if (empty($uri)) {
      return FALSE;
    }
    elseif (preg_match('#^public://#', $uri)) {
      $uri = D7Utils::fileRelativeUri($uri, $this->configuration['folders']['source_public_folder_uri']);
      $uri = $this->configuration['folders']['source_domain'] . '/' . ltrim($uri, '/');
      $row->setSourceProperty('uri', $uri);
    }

    // URL decode
    if (!empty($this->configuration['folders']['urldecode'])) {
      $uri = urldecode($uri);
    }

    // nice file name
    $nice_filename = D7Utils::niceFileName( basename($uri) );

    // file name - @todo potentially abbreviate really long names - @ScottHooker??
    $filename = $row->getSourceProperty('filename');
    $filename = empty($filename) ? $nice_filename : $filename;
    if (!empty($this->configuration['filename_prefix'])) {
      $filename = $this->configuration['filename_prefix'] . $filename;
    }
    $row->setSourceProperty('filename', $filename);

    // destination
    $destination_file_path = $this->configuration['folders']['target_folder'];
    $destination_file_path .= !preg_match('#/$#', $destination_file_path) ? '/' : '';
    $destination_file_path .= $nice_filename;
    $row->setSourceProperty('destination_file_path', $destination_file_path);

    $this->configuration['constants']['source_base_path'] = '';  // hackety hack!
    return parent::prepareRow($row);
  }

}
