<?php
 
namespace Drupal\tg_news2_migration\Plugin\migrate\source;

use Drupal\tg_news2_migration\Plugin\migrate\D7InlineFiles;
use Drupal\tg_news2_migration\Plugin\migrate\D7Query;
use Drupal\tg_news2_migration\Plugin\migrate\D7Tags;
use Drupal\tg_news2_migration\Plugin\migrate\D7Utils;

use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Exception\RequirementsException;

/**
 * Drupal 7 news articles from database.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: d7_news_articles
 *
 * @MigrateSource(
 *   id = "d7_news_articles",
 * )
 */
class D7NewsArticles extends SqlBase {

  /**
   * {@inheritdoc}
   */
  public function checkRequirements() {
    parent::checkRequirements();
    if (!D7Utils::checkSourceDatabase($this->getDatabase())) {
      throw new RequirementsException('Unable to connect to source database, or tables missing');
    }
    elseif (!isset($this->configuration['folders'])) {
      throw new RequirementsException('Config - folders not set');
    }
    elseif (!isset($this->configuration['folders']['source_domain'])) {
      throw new RequirementsException('Config - folders:source_domain not set');
    }
    elseif (!isset($this->configuration['folders']['target_folder'])) {
      throw new RequirementsException('Config - folders:target_folder not set');
    }
    else {
      // make sure the folder exists
      $target_folder = $this->configuration['folders']['target_folder'];
      if (!file_prepare_directory($target_folder, FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
        throw new RequirementsException('Config - folders:target_folder invalid, or unable to create');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('node', 'n');
    $query->fields('n', ['nid', 'title']);
    $query->addField('body', 'body_value', 'body');
    $query->addField('standfirst', 'field_news_article_standfirst_value', 'standfirst');
    $query->addField('post_script', 'field_news_article_post_script_value', 'post_script');
    $query->addField('publication_date', 'field_publication_date_value', 'publication_date');
    $query->addField('author', 'field_news_article_byline_target_id', 'author');

    // rest of selection & joins
    D7Query::nodeQuery($query, 'news_article', $this->configuration);

    // teaser image is derived from NID lookup

    // standfirst
    D7Query::nodeFieldLeftJoin($query, 'field_news_article_standfirst', 'standfirst', $delta = 0);
    // post script
    D7Query::nodeFieldLeftJoin($query, 'field_news_article_post_script', 'post_script', $delta = 0);
    // publication date
    D7Query::nodeFieldLeftJoin($query, 'field_publication_date', 'publication_date', $delta = 0);
    // author
    D7Query::nodeFieldLeftJoin($query, 'field_news_article_byline', 'author', $delta = 0);

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => t('Node Id'),
      'title' => t('Article Title'),
      'body' => t('Article Body'),
      'standfirst' => t('Standfirst content'),
      'post_script' => t('Postscript content'),
      'publication_date' => t('Publication date'),
      'author' => t('Author'),
      // attachments
      'attached_files' => t('Attached files'),
      // taxonomy terms
      'tags' => t('News taxonomy terms'),
      // related content
      'related' => t('Related content'),
      // dummy fields
      'mobile_title' => t('Derived field'),
      'image_alt' => t('Derived field'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');

    $title = $row->getSourceProperty('title');
    if (empty($title)) {
      return FALSE;  # will never happen!
    }

    // 1. get attachments
    if ($attached_files = $this->getNodeFiles($nid, 'field_news_article_files', 'news_article')) {
      $row->setSourceProperty('attached_files', implode(', ', $attached_files));
    }

    // 2. get tags
    $tags = $this->getNodeTags($nid, 'field_news_article_tags', 'news_article');
    $tags += $this->getNodeTags($nid, 'field_news_category', 'news_article');
    $tags += $this->getNodeTags($nid, 'field_news_section', 'news_article');
    if ($tags) {
      foreach ($tags as $tid => $name) {
        if ($xref = D7Tags::getXref($name, $this->configuration)) {
          $tags[$tid] = $xref;
        }
        else {
          $tags[$tid] = NULL;  // just for debugging
        }
      }
      $tags = array_filter($tags);  // remove nulls
      $row->setSourceProperty('tags', implode(', ', $tags));
    }

    // 3. Related content - tba!
    if ($related = $this->getNodeTerm($nid, 'field_news_article_related_conte', 'news_article')) {
      $row->setSourceProperty('related', implode(', ', $related));
    }

    // 4. Derived fields
    $image_alt = $title . ' picture';
    $row->setSourceProperty('image_alt', $image_alt);

    $mobile_title = D7Utils::abbrev($title, 60);
    $row->setSourceProperty('mobile_title', $mobile_title);

    // 5. Convert publication date to yyyy-mm-ddThh:mm:ss format
    if ($row->hasSourceProperty('publication_date')) {
      $value = $row->getSourceProperty('publication_date');
      if ($timestamp = strtotime($value)) {
        $value = date('Y-m-d', $timestamp) . 'T' .date( 'H:i:s', $timestamp);
      }
      else {
        $value = NULL;
      }
      $row->setSourceProperty('publication_date', $value);
    }

    // 6. Inline files
    $body = $row->getSourceProperty('body');
    if (!empty($body)) {
      $body = $this->inlineFiles($body);
      $row->setSourceProperty('body', $body);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['nid']['type'] = 'integer';
    return $ids;
  }

  /**
   * Get node files (file ids).
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  private function getNodeFiles($nid, $fieldname, $bundle) {
    $table = 'field_data_' . $fieldname;
    $fieldname = $fieldname . '_fid';
    //
    $query = $this->select($table, 'f');
    $query->addField('f', $fieldname, 'fid');

    $query->condition('f.entity_id', $nid);
    $query->condition('f.bundle', $bundle);
    $query->condition('f.entity_type', 'node');
    $query->condition('f.deleted', 0);

    $query->orderBy('f.delta');

    // loop...
    $ret = [];
    foreach ($query->execute() as $row) {
      $row = (object) $row;  // prefer objects
      $ret[$row->fid] = $row->fid;  // ensures uniqueness!!
    }

    return $ret;
  }

  /**
   * Get node "tags" (taxonomy terms).
   * The returned array is keyed by term id and includes the term name.
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  private function getNodeTags($nid, $fieldname, $bundle) {
    $table = 'field_data_' . $fieldname;
    $fieldname = $fieldname . '_tid';
    //
    $query = $this->select($table, 'tag');

    $query->join('taxonomy_term_data', 'taxonomy', "tag.${fieldname} = taxonomy.tid");

    $query->addField('tag', $fieldname, 'tid');
    $query->addField('taxonomy', 'name');

    $query->condition('tag.entity_id', $nid);
    $query->condition('tag.bundle', $bundle);
    $query->condition('tag.entity_type', 'node');
    $query->condition('tag.deleted', 0);

    $query->orderBy('tag.delta');

    // loop...
    $ret = [];
    foreach ($query->execute() as $row) {
      $row = (object) $row;
      $ret[$row->tid] = $row->name;
    }

    return $ret;
  }

  /**
   * Get node "terms" (entities).
   * @param $nid
   * @param $fieldname
   * @param $bundle
   * @return array
   */
  private function getNodeTerm($nid, $fieldname, $bundle) {
    $table = 'field_data_' . $fieldname;
    $fieldname = $fieldname . '_target_id';
    //
    $query = $this->select($table, 'term');
    $query->addField('term', $fieldname, 'nid');

    $query->condition('term.entity_id', $nid);
    $query->condition('term.bundle', $bundle);
    $query->condition('term.entity_type', 'node');
    $query->condition('term.deleted', 0);

    $query->orderBy('term.delta');

    // loop...
    $ret = [];
    foreach ($query->execute() as $row) {
      $row = (object) $row;
      $ret[$row->nid] = $row->nid;
    }

    return $ret;
  }

  /**
   * Process inline files, edit body content & create inline files as required.
   *
   * @note So all this extra functionality deal with media embed - this is not currently handled by the SQL database migration, it worked in the CSV import because the data came from the source db via views and theme so embedded media was already converted. Now we can convert it and then edit the embedded JSON [{fid: ... + other attributes}] and then to a token replacement as detailed in https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration but I havent done that yet because I kinda got caught up in other things. Note to @ScottHooker
   *
   * @param $body
   * @return string
   */
  private function inlineFiles($body) {
    $inline = new D7InlineFiles($this->getDatabase(), $this->configuration);

    $body = $inline->parseFiles($body);

    return $body;
  }

}
