<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Tags;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Row;

/**
 * Generate a target tag name.
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   name:
 *     plugin: tg_news_tag_name
 *     source: name
 *     skip_row_if_exists:
 *       - vocab 1
 *       - vocab 2
 *       - ...etc
 *     message: 'Name exists in other vocabularies'
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_news_tag_name"
 * )
 */
class NewsTagName extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    $vocabs = isset($this->configuration['skip_row_if_exists']) ? $this->configuration['skip_row_if_exists'] : [];
    if (Tags::getXref($value, $vocabs)) {
      $msg = !empty($this->configuration['message']) ? $this->configuration['message'] : '';
      $msg = $this->errorMessage($msg);
      throw new MigrateSkipRowException($msg);
    }
    $value = Tags::tidyName($value);

    return $value;
  }

}
