<?php

namespace Drupal\tg_news2_migration\Plugin\migrate;

use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\migrate\MigrateException;

class D7InlineFiles {

  private $database = NULL;  // set connection

  private $source_domain;
  private $source_public_folder_uri;
  private $target_folder;
  private $public_folder_uri;

  // file renaming
  private $rename_by;

  /**
   * Class constructor.
   *
   * @param $database - source database connection
   * @param $sconfiguration
   */
  public function __construct(Connection $database, $configuration) {
    $this->database = $database;

    $this->source_domain = $configuration['folders']['source_domain'];

    $this->source_public_folder_uri =  isset($configuration['folders']['source_public_folder_uri'])
      ? $configuration['folders']['source_public_folder_uri']
      : '/sites/default/files/';

    // make sure target folder ends with a "/"
    $this->target_folder = $configuration['folders']['target_folder'];
    $this->target_folder .= !preg_match('#/$#', $this->target_folder) ? '/' : '';

    $this->public_folder_uri = isset($configuration['folders']['public_folder_uri'])
      ? $configuration['folders']['public_folder_uri']
      : '/sites/default/files/';

    $this->rename_by = isset($configuration['folders']['rename_by'])
      ? $configuration['folders']['rename_by']
      : 'prefix';
  }

  /**
   * Parse inline files.
   * @param $body
   * @throws \Drupal\migrate\MigrateException
   * @return string
   */
  public function parseFiles($body) {
    // images & links
    foreach ($this->parseForTags($body) as $source => $target) {
      $url = D7Utils::getFile($source, $target['uri'], $target['file'], $this->public_folder_uri);
      $body = str_replace($source, $url, $body);
    }

    // media
    foreach ($this->parseForMedia($body) as $source => $target) {
      if (!empty($target['subfolder'])) {
        if (!file_prepare_directory($target['subfolder'], FILE_CREATE_DIRECTORY|FILE_MODIFY_PERMISSIONS)) {
          throw new MigrateException('Inline files - subfolder "' . $target['subfolder'] . '"  invalid, or unable to create');
        }
      }
      $url = D7Utils::getFile($source, $target['uri'], $target['file'], $this->public_folder_uri);
      $fragment = str_replace('[:url]', $url, $target['fragment']);
      $body = str_replace($target['replace'], $fragment, $body);
    }

    return $body;
  }

  /**
   * Parse embedded media elements.
   * Thanks -> https://blog.kalamuna.com/news/converting-drupal-7-media-tags-during-a-drupal-8-migration
   * @param $content
   * @return array
   */
  private function parseForMedia($content) {
    $files = [];
    if (preg_match_all('/\[\[(.*?)\]\]/s', $content, $matches)) {
      foreach ($matches[1] as $media) {
        switch (TRUE) {
          case !$json = json_decode($media):   // could also use Json:decode()
          case empty($json->fid):
          case !$file = D7Query::getFileObject($this->database, $json->fid):
            continue 2;
          default:
        }
        $parts = D7Utils::mediaParts($file, $json, $this->source_domain, $this->source_public_folder_uri, $this->target_folder, $this->rename_by);
        if (!empty($parts->tag)) {
          $files[$parts->source_uri] = [
            'file' => $parts->target_file,
            'uri' => $parts->target_uri,
            'replace' => '[[' . $media . ']]',
            'fragment' => $parts->tag,
            'subfolder' => $parts->subfolder,
          ];
        }
      }
    }
    return $files;
  }

  /**
   * Parses inline file tags - this is an instantiated clone of the InlineFiles::parseFiles method.
   * @param $content
   * @return array
   */
  private function parseForTags($content) {
    $files = [];
    // image and link files
    if (preg_match_all('/<(a|img) [^>]*>/i', $content, $matches)) {
      foreach ($matches[1] as $key => $tag) {
        if ($uri = $this->extractUri($tag, $matches[0][$key])) {
          $source_uri = (!preg_match('/https?:\/\//', $uri) ? $this->source_domain : '') . $uri;
          //
          list($ignore, $filename) = D7Utils::filePathAndName($uri);
          $filename = D7Utils::niceFileName($filename);
          //
          $target_uri = $this->target_folder . $filename;
          $target_file = \Drupal::service('file_system')->realpath($target_uri);
          //
          $files[$source_uri] = [
            'file' => $target_file,
            'uri' => $target_uri,
            //'replace' => $source_uri,
          ];
        }
      }
    }
    return $files;
  }

  /**
   * Extract filename URI from image/anchor fragment.
   * @param $tag
   * @param $fragment
   * @return string | null
   */
  private function extractUri($tag, $fragment) {
    $regex = ($tag == 'a' || $tag == 'A') ? 'href' : 'src';
    $regex = '/' . $regex . '="([^"]+)/i';
    switch (TRUE) {
      case !preg_match($regex, $fragment, $matches):
      case !preg_match('#sites/default/files#', $matches[1]):
        break;
      default:
        return $matches[1];
    }
    return NULL;
  }
}
