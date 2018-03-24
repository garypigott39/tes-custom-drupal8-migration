<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate a twitter handle from supplied string.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tg_twitter_handle
 *     source: handle
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_twitter_handle"
 * )
 */
class TwitterHandle extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (is_string($value)) {
      $value = ltrim($value, '@');
      if (!Utils::isEmpty($value)) {
        $value = '@' . $value;
      }
    }
    return $value;
  }

}