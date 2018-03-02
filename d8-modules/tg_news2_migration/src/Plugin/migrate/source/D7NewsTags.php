<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\source;

use Drupal\tg_news2_migration\Plugin\migrate\D7Utils;
use Drupal\tg_news2_migration\Plugin\migrate\D7Tags;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Drupal 7 taxonomy terms source from database.
 *
 * @MigrateSource(
 *   id = "d7_news_tags",
 *   source_provider = "taxonomy",
 * )
 */
class D7NewsTags extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!D7Utils::checkSourceDatabase($this->getDatabase())) {
      throw new RequirementsException('Unable to connect to source database, or tables missing');
    }
    elseif (isset($this->configuration['required']['vids'])) {
      // check that the source tags are populated
      if (!D7Utils::checkRefdataPopulated($this->configuration['required']['vids'])) {
        throw new RequirementsException('required:vids - refdata vocabularies are not populated');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // check
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
    $query->distinct();
    return $query;
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
  public function prepareRow(Row $row) {
    $name = $row->getSourceProperty('name');
    if (empty($name) || D7Tags::getXref($name, $this->configuration)) {
      return FALSE;
    }

    $name = D7Tags::tidyName($name);
    $row->setSourceProperty('name', $name);

    //
    return parent::prepareRow($row);
  }
 
  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['tid']['type'] = 'integer';
    return $ids;
  }
 
}
