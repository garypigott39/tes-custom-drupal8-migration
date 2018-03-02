<?php

namespace Drupal\tg_news_migration\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides the ability to include optional filtering of source rows.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: news_file_csv
 *   filter:
 *     fieldname: filetype
 *     exclude:
 *       - 'my unrequired value'  # alternatively can use include filter
 *     include:
 *       - 'my target value'
 *     ignorecase: true  # default false
 *
 * Note, the use of the literal '@empty' to indicate empty value.
 *
 *   ... rest as per csv
 * @endcode
 *
 *
 * @MigrateSource(
 *   id = "news_file_csv"
 * )
 */
class NewsFileCSV extends TesNewsCSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$this->filterRow($row)) {
      return FALSE;
    }
    // check no domain name
    
    // @todo: gubbins
    return parent::prepareRow($row);
  }

  /**
   * Filter supplied content.
   * @param \Drupal\migrate\Row $row
   * @throws \Drupal\migrate\MigrateException
   * @return bool
   */
  private function filterRow(Row $row) {
    if (isset($this->configuration['filter'])) {
      $filter = $this->configuration['filter'];
      if (!isset($filter['fieldname'])) {
        throw new MigrateException('filter:fieldname is empty');
      }
      if (!isset($filter['include']) && !isset($filter['exclude'])) {
        throw new MigrateException('filter:include and filter:exclude is empty');
      }
      // source field
      $fieldname = $filter['fieldname'];
      list($filter_values, $include_empty, $exclude_empty) =
        $this->buildFilters($filter, !empty($filter['ignorecase']));
      //
      $value = $row->hasSourceProperty($fieldname)
        ? trim($row->getSourceProperty($fieldname))
        : NULL;
      $value = !empty($filter['ignorecase']) ? strtolower($value) : $value;
      // include or exclude?
      switch (TRUE) {
        case !empty($filter_values['include']):
          if (!$include_empty && empty($value)) {
            return FALSE;
          }
          if (!in_array($value, $filter_values['include'])) {
            return FALSE;
          }
          break;
        case $exclude_empty && empty($value):
        case in_array($value, $filter_values['exclude']):
          return FALSE;
        default:
      }
    }
    return TRUE;
  }

  /**
   * Populate filters array.
   * @param $filter - from config
   * @param $ignorecase
   * @return array
   */
  private function buildFilters($filter, $ignorecase = FALSE) {
    // populate filter values
    static $filter_values, $include_empty, $exclude_empty;
    if (!isset($filter_values)) {
      $include_empty = $exclude_empty = FALSE;
      $filter_values['include'] = $filter_values['exclude'] = [];
      if (isset($filter['include'])) {
        foreach ($filter['include'] as $value) {
          $value = $ignorecase ? strtolower($value) : $value;
          if ($value == '@empty') {
            $include_empty = TRUE;
          }
          else {
            $filter_values['include'][] = $value;
          }
        }
      }
      else {
        foreach ($filter['exclude'] as $value) {
          $value = $ignorecase ? strtolower($value) : $value;
          if ($value == '@empty') {
            $exclude_empty = TRUE;
          }
          else {
            $filter_values['exclude'][] = $value;
          }
        }
      }
    }
    return array($filter_values, $include_empty, $exclude_empty);
  }

}
