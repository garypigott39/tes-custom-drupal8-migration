<?php

namespace Drupal\tg_news_migration\Plugin\migrate\source;

use Drupal\tg_news_migration\Plugin\migrate\HelperUtils;
use Drupal\migrate\Row;
use Drupal\tg_news_migration\Plugin\migrate\InlineFiles;

/**
 * Supports optional "files_folder" parameter.
 *
 * @MigrateSource(
 *   id = "news_articles_csv"
 * )
 */
class NewsArticlesCSV extends TesNewsCSV {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // aide memoire -> potentially useful:  $row->removeDestinationProperty()

    // mobile title - @todo: tba
    $mobile_title = HelperUtils::abbrev( $row->getSourceProperty('title') );
    $row->setSourceProperty('mobile_title', $mobile_title);

    // belt&braces - this should already be plain text
    if ($row->hasSourceProperty('standfirst')) {
      $value = trim($row->getSourceProperty('standfirst'));
      $row->setSourceProperty('standfirst', strip_tags($value));
    }

    // recreate the tags stuff
    $tags = HelperUtils::getTags($row, 'tags');
    if ($categories = HelperUtils::getTags($row, 'category', 'News Categories: ')) {
      $tags = array_merge($tags, $categories);
    }
    if ($sections = HelperUtils::getTags($row, 'section', 'News Sections: ')) {
      $tags = array_merge($tags, $sections);
    }
    $tags = array_filter($tags);
    $row->setSourceProperty('tags', implode(', ', $tags));

    // set teaser image
    if ($row->hasSourceProperty('teaser')) {
      $value = $row->getSourceProperty('teaser');
      if (empty($value) && $row->hasSourceProperty('images')) {
        $images = $row->getSourceProperty('images');
        if (!empty($images)) {
          // only want 1 image
          $value = explode(', ', $images);
          $row->setSourceProperty('teaser', $value[0]);
        }
      }

      if (!empty($value)) {
        $title = 'Image for: ' . $row->getSourceProperty('title');
        $row->setSourceProperty('image_alt', $title);
      }
    }

    // convert publication date to yyyy-mm-ddThh:mm:ss format
    if ($row->hasSourceProperty('publication')) {
      $value = $row->getSourceProperty('publication');
      if ($timestamp = strtotime($value)) {
        $value = date('Y-m-d', $timestamp) . 'T' .date( 'H:i:s', $timestamp);
      }
      else {
        $value = NULL;
      }
      $row->setSourceProperty('publication', $value);
    }

    // inline files
    if ($row->hasSourceProperty('body')) {
      $body = $row->getSourceProperty('body');
      $body = $this->processInlineFiles($body);
      $row->setSourceProperty('body', $body);
    }

    /* -> attached files no longer supported

    // attached files - strip domain gubbins
    if ($files = HelperUtils::getAbsoluteFilenames($row, 'files')) {
      $row->setSourceProperty('files', implode(', ', $files));
    }
    */

    return parent::prepareRow($row);
  }

  /**
   * Process inline files.
   * @param $body
   * @throws \Drupal\migrate\MigrateException
   * @return string
   */
  private function processInlineFiles($body) {
    $source_domain = isset($this->configuration['source_domain'])
      ? $this->configuration['source_domain']
      : 'https://tes.com';
    $target_folder = isset($this->configuration['files_folder'])
      ? $this->configuration['files_folder']
      : 'public://';
    $public_folder_uri =  isset($this->configuration['public_folder_uri'])
      ? $this->configuration['public_folder_uri']
      : '/sites/default/files/';

    // make sure the folder exists
    if (!file_prepare_directory($target_folder, FILE_CREATE_DIRECTORY|FILE_MODIFY_PERMISSIONS)) {
      throw new MigrateException('files_folder:invalid folder, or unable to create');
    }
    elseif ($files = InlineFiles::parseFiles($body, $source_domain, $target_folder)) {
      foreach ($files as $source => $target) {
        // try to get the file guzzle it!
        if (!file_exists($target['file'])) {
          system_retrieve_file($source, $target['uri']); // unmanaged file.
        }
        //$url = file_create_url($target['uri']); -- didnt work!
        $url = HelperUtils::getFileRelativeUrl($target['uri'], $public_folder_uri);
        // replace the file name in body
        $body = str_replace($source, $url, $body);
      }
    }
    return $body;
  }
}
