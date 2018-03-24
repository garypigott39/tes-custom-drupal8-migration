<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;
use Drupal\tg_migration\Plugin\utils\InlineFiles as FilesParser;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Process inline files in body content. Said files will be downloaded as 
 * unmanaged files and tags converted as appropriate.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   body:
 *     plugin: tg_inline_files
 *     source: body
 *     file_source:
 *       database: news_migrate  # only required if media true
 *       domain:   'https://tes.com'
 *       public_folder_uri: '/sites/default/files'  # the default
 *     file_target:
 *       folder: 'public://myfolder'
 *       public_folder_uri: '/sites/default/files'  # the default
 *       rename_by: subfolder  # for media tags with view modes, options are:
 *                             # prefix (file prefix) or subfolder
 *       media: true           # convert media tags
 *       shorten: 60           # shorten long filenames to 60 characters
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_inline_files"
 * )
 */
class InlineFiles extends BaseProcessPlugin {

  private $database_connection;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (isset($value)) {
      // So all this extra functionality deal with media embed - this is not currently handled by the SQL
      // database migration, it worked in the CSV import because the data came from the source db via views and theme
      // so embedded media was already converted. Now we can convert it and then edit the embedded JSON [{fid: ... +
      // other attributes}] and then to a token replacement.
      //
      // Thanks also to https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration

      $parser = new FilesParser(
        $this->database_connection,
        $this->configuration['file_source'],
        $this->configuration['file_target']
      );
      $value = $parser->ParseFiles($value);
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    if (!isset($this->configuration['file_source'])) {
      throw new RequirementsException('file_source undefined');
    }
    elseif (!isset($this->configuration['file_source']['domain'])) {
      throw new RequirementsException('file_source:domain undefined');
    }
    elseif (!isset($this->configuration['file_target'])) {
      throw new RequirementsException('file_target undefined');
    }
    elseif (!isset($this->configuration['file_target']['folder'])) {
      throw new RequirementsException('file_target:folder undefined');
    }
    elseif (!file_prepare_directory($this->configuration['file_target']['folder'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
      throw new RequirementsException('file_target:folder invalid, or unable to create');
    }
    // establish SOURCE database connection?
    if (isset($this->configuration['file_source']['database'])) {
     if (!$database = Utils::getDatabase($this->configuration['file_source']['database'])) {
       throw new RequirementsException('unable to create source database connection');
     }
     $this->database_connection = $database;
    }
    else {
      $this->database_connection = NULL;
    }
  }

}
