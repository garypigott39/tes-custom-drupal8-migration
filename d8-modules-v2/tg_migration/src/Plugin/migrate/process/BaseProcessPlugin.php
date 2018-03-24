<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Wrapper for \ProcessPluginBase.
 * @see Core ProcessPluginBase for more details.
 */
abstract class BaseProcessPlugin extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $this->initConfig($row);

    $this->checkRequirements();

    if (is_string($value)) {
      $value = $this->trim($value);
    }
    if (is_array($value)) {
      $value = Utils::arrayTrimElements($value);
    }

    if (!empty($this->configuration['urldecode'])) {
      $value = $this->urldecode($value);
    }
    elseif (!empty($this->configuration['urlencode'])) {
      $value = $this->urlencode($value);
    }

    return $value;
  }

  /**
   * Expose a checkRequirements method on transformation.
   * @throws \Drupal\migrate\Exception\RequirementsException;
   */
  public function checkRequirements() {
    //@todo - as appropriate for child methods
  }

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  protected function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->pluginId, $msg, $args);
  }

  /**
   * Initialise process plugin config. Basically a (much) simplified version of the
   * get functionality...
   * @param \Drupal\migrate\Row $row
   * @return void
   */
  protected function initConfig(Row $row) {
    Utils::initProcessConfig($this->configuration, $row);
  }

  /**
   * Trim supplied value.
   * @param $value
   * @return array|string
   */
  private function trim($value) {
    return is_array($value) ? array_map('trim', $value) : trim($value);
  }

  /**
   * Urldecode supplied value.
   * @param $value
   * @return array|string
   */
  private function urldecode($value) {
    $value = $this->trim($value);
    return is_array($value) ? array_map('urldecode', $value) : urldecode($value);
  }

  /**
   * Urlencode supplied value.
   * @param $value
   * @return array|string
   */
  private function urlencode($value) {
    $value = $this->trim($value);
    return is_array($value) ? array_map('urlencode', $value) : urlencode($value);
  }
}
