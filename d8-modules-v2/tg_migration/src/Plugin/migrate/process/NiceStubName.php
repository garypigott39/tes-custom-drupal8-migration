<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a nice stub name.
 * @see Core ProcessPluginBase for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tg_nice_stub_name
 *     source: title
 *     prefix: 'Stub: '
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_nice_stub_name"
 * )
 */
class NiceStubName extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if ($row->isStub()) {
      $prefix = $this->configuration['prefix'];
      $value = $prefix . (!empty($value) ? $value : uniqid('', TRUE));
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    //
    if (!isset($this->configuration['prefix'])) {
      throw new RequirementsException('prefix undefined');
    }
  }

}

