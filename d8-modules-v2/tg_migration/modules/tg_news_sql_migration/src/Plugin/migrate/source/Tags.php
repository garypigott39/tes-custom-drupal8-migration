<?php
 
namespace Drupal\tg_news_sql_migration\Plugin\migrate\source;

use Drupal\tg_migration\Plugin\migrate\source\SQL;
use Drupal\tg_migration\Plugin\utils\Tags as TagsUtils;
use Drupal\tg_migration\Plugin\utils\Utils;

use Drupal\migrate\Row;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Drupal 7 taxonomy terms source from database.
 *
 * @MigrateSource(
 *   id = "tg_sql_tags",
 *   source_provider = "taxonomy",
 * )
 */
class Tags extends SQL {

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
  public function query() {
    // multiple vocabularies
    $vocabs[':name[]'] = [
      'News Tags',
      'News Categories',
      'News Sections',
    ];
    $query = $this->select('taxonomy_term_data', 'td');
    $query->join('taxonomy_vocabulary', 'tv', 'td.vid = tv.vid AND tv.name IN (:name[])', $vocabs);
    $query->fields('td', [
      'tid',
      'vid',
      'name',
      'description',
      'weight',
      'format'
    ]);

    $query->orderBy('td.name');

    //$query->distinct();

    return $query;
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

    //
    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'tid' => $this->t('The term ID.'),
      'vid' => $this->t('Existing term VID'),
      'name' => $this->t('The name of the term.'),
      'description' => $this->t('The term description.'),
      'weight' => $this->t('Weight'),
      'parent' => $this->t("The Drupal term IDs of the term's parents."),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['name'] = [
      'type' => 'string',
      'alias' => 'td',
    ];
    return $ids;
  }
}
