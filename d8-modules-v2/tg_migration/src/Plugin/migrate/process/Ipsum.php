<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Generate some placeholder text. Thanks to https://loripsum.net/
 * @see Core ProcessPluginBase for more details.
 *
 * Example:
 *
 * @code
 * process:
 *   bio:
 *     plugin: tg_ipsum
 *     source: bio
 *     ipsum:
 *       num_paragraphs: 3
 *       paragraph_length: medium  # short/medium/long - default medium
 *       plain_text: false  # the default
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "tg_ipsum"
 * )
 */
class IpSum extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    if (Utils::isEmpty($value)) {
      $value = $this->ipsum();
    }
    return $value;
  }

  /**
   * Generate ipsum using helpful API.
   * @param string $url
   * @return string
   */
  private function ipsum($url = 'https://loripsum.net/api') {
    $num_paragraphs = isset($this->configuration['ipsum']['num_paragraphs'])
      ? (int) $this->configuration['ipsum']['num_paragraphs']
      : 1;
    $paragraph_length = isset($this->configuration['ipsum']['paragraph_length'])
      ? $this->configuration['ipsum']['paragraph_length']
      : 'medium';
    $plain_text = !empty($this->configuration['ipsum']['plain_text']);
    //
    $url .= '/' . $num_paragraphs . '/' . $paragraph_length;

    if ($text = file_get_contents($url)) {
      $text = $plain_text ? Utils::plainText($text) : $text;
    }
    else {
      $text = 'Unable to generate ipsum';
    }

    return $text;
  }
}
