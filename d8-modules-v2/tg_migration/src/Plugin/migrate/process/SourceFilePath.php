<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a source file pathname.
 * @see Core ProcessPluginBase for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * Example:
 *
 * @code
 * process:
 *   file_source:
 *     plugin: tg_source_file_path
 *     source: uri
 *     file_source:
 *       domain:   'https://tes.com'
 *       public_folder_uri: '/sites/default/files'  # the default
 * @endcode
 *
 * Can be used with local file-paths by using the "local_file: domain-path" setting.
 *
 * @code
 * process:
 *   file_source:
 *     plugin: tg_source_file_path
 *     source: uri
 *     file_source:
 *       domain:   '/var/myfiles'
 *       public_folder_uri: '/sites/default/files'  # the default
 *       local_file: 'tes.com'  # excluding the protocol
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_source_file_path"
 * )
 */
class SourceFilePath extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    // handle the value
    if (isset($value)) {
      if (preg_match('#^public://#', $value)) {
        $public_folder_uri = isset($this->configuration['file_source']['public_folder_uri'])
          ? $this->configuration['file_source']['public_folder_uri'] : '/sites/default/files';
        //
        $value = Utils::fileRelativeUri($value, $public_folder_uri);
        $value = Utils::buildPathName($this->configuration['file_source']['domain'], $value);
      }
      elseif (!preg_match('#https?://#', $value)) {
        $value = Utils::buildPathName($this->configuration['file_source']['domain'], $value);
      }
      elseif (!empty($this->configuration['file_source']['local_file'])) {
        // replace domain with file path
        $regex = '#https?://' . trim($this->configuration['file_source']['local_file'], '/') . '/#';
        $value = preg_replace($regex, $this->configuration['file_source']['domain'], $value);
      }
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
  }

}
