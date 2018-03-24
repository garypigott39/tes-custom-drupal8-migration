<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Trim supplied character from string.
 * @see Core ProcessPluginBase for more details.
 * 
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: tg_trim
 *     source: filename
 *     character: '/'
 *     callback: trim  # ltrim or rtrim
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_trim"
 * )
 */
class TrimCharacters extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (isset($value)) {
      $char = isset($this->configuration['character']) ? $this->configuration['character'] : NULL;
      $callback = isset($this->configuration['callback']) ? $this->configuration['callback'] : 'trim';
      if (!preg_match('/^(trim|ltrim|rtrim)$/', $callback)) {
        $callback = 'trim';
      }
      $value = strlen($char) > 0 ? $callback($value, $char) : $callback($value);
    }
    return $value;
  }

}
