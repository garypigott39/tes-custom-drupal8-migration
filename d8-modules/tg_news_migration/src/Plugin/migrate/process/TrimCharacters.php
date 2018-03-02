<?php

namespace Drupal\tg_news_migration\Plugin\migrate\process;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Trim named character from a string.
 *
 * Example:
 *
 * @code
 * process:
 *   image_file:
 *     plugin: trim_chars
 *     character: '/'
 *     callback: trim  # ltrim or rtrim
 * @endcode
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "trim_characters"
 * )
 */
class TrimCharacters extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = $value = trim($value);
    if (empty($value)) {
      return NULL;
    }

    $char = isset($this->configuration['character'])
      ? $this->configuration['character'] : NULL;
    $callback = isset($this->configuration['callback']) 
      ? $this->configuration['callback'] : 'trim';
    if (!preg_match('/^(trim|ltrim|rtrim)$/', $callback)) {
      $callback = 'trim'; 
    }
    $value = isset($char) ? $callback($value, $char) : $callback($value);

    return $value;
  }
}
