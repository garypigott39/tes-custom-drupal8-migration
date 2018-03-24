<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Convert string to plain text - striping all tags and running entity decode
 * on the supplied string.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   title:
 *     plugin: tg_plain_text
 *     source: title
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_plain_text"
 * )
 */
class PlainText extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    return Utils::plainText($value);
  }

}
