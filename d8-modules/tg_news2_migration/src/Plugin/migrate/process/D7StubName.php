<?php

namespace Drupal\tg_news2_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a stub name.
 * @see Core process plugin for more details. * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: d7_stub_name
 *     source: title
 *     prefix: 'Stub: '
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "d7_stub_name"
 * )
 */
class D7StubName extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['prefix'])) {
      throw new MigrateException('prefix undefined');
    }
    else {
      $prefix = $this->configuration['prefix'];
    }

    if (isset($this->configuration['source'])) {
      $fieldname = $this->configuration['source'];
      $value = $row->hasSourceProperty($fieldname) ? $row->getSourceProperty($fieldname) : '';
    }
    $value = trim($value);

    // if stub then add prefix
    if ($row->isStub()) {
      $value = $prefix . (!empty($value) ? $value : uniqid('', TRUE));
    }

    return $value;
  }
}

