<?php

namespace Drupal\tg_migration\Plugin\migrate;

use Drupal\tg_migration\Plugin\migrate\source\CSV;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Base custom CSV class. Intended for use where the source data is difficult to
 * supply via a query or an actual CSV file doesnt exist.
 *
 * It is intended to be used as a parent class for such difficult migration sources,
 * where we will invoke the pre-flight functionality.
 *
 * Thanks also to https://evolvingweb.ca/blog/writing-custom-migration-source-plugin-drupal-8
 */
abstract class CustomCSV extends CSV {

  private $customPathName;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    try {
      $this->getCustomData();
      $this->prePopulateMapTable();
    }
    catch (\Exception $e) {
      $msg = $this->errorMessage( $e->getMessage() );
      throw new RequirementsException($msg);
    }
    //
    $this->configuration['path'] = $this->customPathName;
    $this->configuration['filename'] = NULL;
    //
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
  }

  /**
   * Expose get custom data handler.
   * @throws \Exception
   * @return void
   */
  public function getCustomData() {
    //@todo populate named CSV style file
    //
    // 1. File must be CSV format - use php's putcsv functionality or whatever
    // 2. set pathname - $this->setCustomPathname('pathname to file');
    // 3. if any error throw an Exception - throw new Exception('Oops');
  }

  /**
   * Expose get custom pathname handler.
   * @return string | void
   */
  protected function getCustomPathname() {
    return $this->customPathName;
  }

  /**
   * Expose set custom pathname handler.
   * @param $pathname
   * @return void
   */
  protected function setCustomPathname($pathname) {
    $this->customPathName = $pathname;
  }

  /**
   * Expose pre-flight populate map table
   * @throws \Exception
   * @return void
   */
  public function prePopulateMapTable() {}

}
