<?php

namespace Drupal\tg_news_csv_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\CSV;
use Drupal\tg_migration\Plugin\utils\Tags as TagsUtils;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Row;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Extends our custom CSV class - for tags.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: tg_csv_tags
 *   # @see \Drupal\tg_migration\Plugin\migrate\source\CSV
 * @endcode
 *
 * @MigrateSource(
 *   id = "tg_csv_tags"
 * )
 */
class Tags extends CSV {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (isset($this->configuration['required']['vids'])) {
      // check that the source tags are populated
      if (!Utils::checkRefdataPopulated($this->configuration['required']['vids'])) {
        $msg = $this->errorMessage('required:vids - refdata vocabularies are not populated');
        throw new RequirementsException($msg);
      }
    }
    if (isset($this->configuration['tags_xref'])) {
      if (!is_array($this->configuration['tags_xref'])) {
        $msg = $this->errorMessage('tags_xref - must be an array');
        throw new RequirementsException($msg);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $vocabs = isset($this->configuration['tags_xref']) ? $this->configuration['tags_xref'] : [];

    // lets be pragmatic, this is the simplest way to edit the tags and do the XREF gubbins & ignore
    $name = $row->getSourceProperty('name');
    if (empty($name) || TagsUtils::getXref($name, $vocabs)) {
      return FALSE;
    }

    $name = TagsUtils::tidyName($name);
    $row->setSourceProperty('name', $name);

    return parent::prepareRow($row);
  }
}
