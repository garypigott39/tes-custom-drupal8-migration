<?php

namespace Drupal\tg_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate_source_csv\Plugin\migrate\source\CSV as BaseCSV;

/**
 * Custom class CSV extends migrate_source_csv version.
 * @see \Drupal\migrate_source_csv\Plugin\migrate\source\CSV
 *
 * Note, we have added a pseudo check-requirements which will throw a RequirementsException
 * in prepareRow() to cause it to fail dramatically!
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_csv
 *   path: './my-data/folder/'  # folder
 *   filename: 'filename.csv'   # filename
 *   # rest of parameters as per standard CSV,
 *   # @see \Drupal\migrate_source_csv\Plugin\migrate\source\CSV
 * @endcode
 *
 * @MigrateSource(
 *   id = "tg_csv"
 * )
 */
class CSV extends BaseCSV {

  private $requirementsError;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    if (isset($this->configuration['filename'])) {
      $this->configuration['path'] = Utils::buildPathName(
        $this->configuration['path'],
        $this->configuration['filename']
      );
    }
    $this->requirementsError = NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $this->requirementsError = NULL;
    try {
      // Migration falls over if file doesnt exist, so here we give an error instead!
      if (!file_exists($this->configuration['path'])) {
        $msg = $this->errorMessage(
          'Unable to get source data CSV file "@path".', [
            '@path' => $this->configuration['path']
          ]
        );
        throw new RequirementsException($msg);
      }
      // check requirements -> not implemented in CSV, hence...
      $this->checkRequirements();
    }
    catch (\Exception $e) {
      $this->requirementsError = $e->getMessage();
    }

    return parent::initializeIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!empty($this->requirementsError)) {
      throw new RequirementsException($this->requirementsError);
    }
    return parent::prepareRow($row);
  }

  /**
   * Expose checkRequirements to child classes for pre-flight checks.
   * @throws \Drupal\migrate\Exception\RequirementsException;
   */
  public function checkRequirements() {}

  /**
   * Standard error message.
   * @param $msg
   * @param $args
   * @return string
   */
  protected function errorMessage($msg, array $args = []) {
    return Utils::errorMessage($this->migration->id(), $msg, $args);
  }

  /**
   * Set requirements error.
   * @return string;
   */
  protected function getRequirementsError() {
    return $this->requirementsError;
  }

  /**
   * Set requirements error.
   * @param $msg
   * @return void
   */
  protected function setRequirementsError($msg) {
    $this->requirementsError = $msg;
  }

}
