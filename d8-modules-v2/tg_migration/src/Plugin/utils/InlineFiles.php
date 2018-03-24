<?php

namespace Drupal\tg_migration\Plugin\utils;

use Drupal\migrate\MigrateException;

/**
 * Manipulate inline files in migration.
 */
class InlineFiles {

  private $database;  // set connection

  private $source_domain;
  private $source_public_folder_uri;
  private $target_folder;
  private $public_folder_uri;

  // media filess included?
  private $media_files;

  // file renaming
  private $rename_by;

  // shorten filenames
  private $shorten;

  /**
   * Class constructor.
   * @param $database_connection
   * @param $source
   * @param $target
   */
  public function __construct($database_connection, array $source, array $target) {
    // source
    $this->database = $database_connection;

    $this->source_domain = $source['domain'];

    $this->source_public_folder_uri = isset($source['public_folder_uri'])
      ? $source['public_folder_uri'] : '/sites/default/files/';

    // target
    $this->target_folder = $target['folder'];

    $this->public_folder_uri = isset($target['public_folder_uri'])
      ? $target['public_folder_uri']
      : '/sites/default/files/';

    $this->media_files = !is_null($this->database) && !empty($target['media']);

    $this->rename_by = isset($target['rename_by']) ? $target['rename_by'] : 'prefix';

    $this->shorten = isset($target['shorten']) ? (int) $target['shorten'] : 0;
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
      if (!$target['file']) {
        throw new MigrateException('Unable to get file for "' . $target['uri'] . '"');
      }
      // shorten filename?
      $target['uri']  = Utils::shortFileName($target['uri'], $this->shorten);
      $target['file'] = Utils::shortFileName($target['file'], $this->shorten);
      //
      $url = Utils::getFile($source, $target['uri'], $target['file'], $this->public_folder_uri);
      //
      $body = str_replace($target['replace'], $url, $body);
    }

    // media?
    if ($this->media_files) {
      foreach ($this->parseForMedia($body) as $source => $target) {
        if (!empty($target['subfolder'])) {
          if (!file_prepare_directory($target['subfolder'], FILE_CREATE_DIRECTORY | FILE_MODIFY_PERMISSIONS)) {
            throw new MigrateException('Inline files - subfolder "' . $target['subfolder'] . '"  invalid, or unable to create');
          }
        }
        // shorten filename?
        $target['uri']  = Utils::shortFileName($target['uri'], $this->shorten);
        $target['file'] = Utils::shortFileName($target['file'], $this->shorten);
        //
        $url = Utils::getFile($source, $target['uri'], $target['file'], $this->public_folder_uri);
        //
        $fragment = str_replace('[:url]', $url, $target['fragment']);
        $body = str_replace($target['replace'], $fragment, $body);
      }
    }

    return $body;
  }

  // Private methods

  /**
   * Extract filename URI from image/anchor fragment.
   * @param $tag
   * @param $fragment
   * @return string | null
   */
  private function extractUri($tag, $fragment) {
    // tags regex
    $regex = ($tag == 'a' || $tag == 'A') ? 'href' : 'src';
    $regex = '/' . $regex . '="([^"]+)/i';
    // sites folder regex
    $folder_regex = '#' . $this->source_public_folder_uri . '#';
    //
    switch (TRUE) {
      case !preg_match($regex, $fragment, $matches):
      case !preg_match($folder_regex, $matches[1]):
        break;
      default:
        return $matches[1];
    }
    return NULL;
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
          case !$file = Query::getFileObject($this->database, $json->fid):
            continue 2;
          default:
        }
        $parts = Utils::mediaParts($file, $json, $this->source_domain, $this->source_public_folder_uri, $this->target_folder, $this->rename_by);
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
          $source_uri = !preg_match('#https?://#', $uri)
            ? Utils::buildPathName($this->source_domain, $uri)
            : $uri;
          //
          list($ignore, $filename) = Utils::filePathAndName($uri);
          $filename = Utils::niceFileName($filename);
          //
          $target_uri = Utils::buildPathName($this->target_folder, $filename);
          $target_file = Utils::realFilePath($target_uri);
          //
          $files[$source_uri] = [
            'file' => $target_file,
            'uri' => $target_uri,
            'replace' => $uri,  // actual bit to replace
          ];
        }
      }
    }
    return $files;
  }
}
