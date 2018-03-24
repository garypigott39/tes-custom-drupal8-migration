<?php

namespace Drupal\tg_migration\Plugin\migrate\process;

use Drupal\migrate\Exception\RequirementsException;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Filter based on a field value, or field value list.
 * @see Core ProcessPluginBase and SkipOnEmpty for more details.
 *
 * Notice we throw RequirementsException's because we want it to fail badly if it
 * isn't able to run. Returns a value of NULL if fails filter.
 *
 * Example:
 *
 * @code
 * process:
 *   name:
 *     plugin: tg_filter
 *     source: fieldname
 *     match:
 *       - '/^alpha/i'
 *       - '/beta$/i'
 *     operator: and    # and/or
 *     negate: true     # default to false, so filter by not matches
 * @endcode
 *
 * Note, assumes that regex supplied are valid.
 *
 * @MigrateProcessPlugin(
 *   id = "tg_filter"
 * )
 */
class Filter extends BaseProcessPlugin {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);

    if (!$this->matches($value)) {
      $value = NULL;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!isset($this->configuration['match'])) {
      $msg = $this->errorMessage('match undefined');
      throw new RequirementsException($msg);
    }
  }

  /**
   * Checks value against matches rules.
   * @param $value
   * @return bool
   */
  private function matches($value) {
    $operator = isset($this->configuration['operator']) ? strtolower($this->configuration['operator']) : 'and';
    $not = !empty($this->configuration['negate']);

    $rules = $this->configuration['match'];
    $rules = !is_array($rules) ? [$rules] : $rules;

    $result = TRUE;
    foreach ($rules as $regex) {
      $true_or_false = $not ? !preg_match($regex, $value) : preg_match($regex, $value);
      $result = $operator == 'and' ? ($result && $true_or_false) : ($result || $true_or_false);
    }

    return $result;
  }
}
