<?php

namespace Drupal\tg_news2_migration\Plugin\migrate;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Database\Connection;

class D7Utils {

  // Requirements

  /**
   * Pre flight requirements check (Source D7 tables exist).
   * @param $database
   * @param $tables
   * @return bool
   */
  public static function checkSourceDatabase(Connection $database, $tables = ['node', 'system', 'users']) {
    foreach ($tables as $table) {
      try {
        $query = $database->select($table, 't');
        $query->addExpression('COUNT(*)', 'ct');
        $result = $query->execute()->fetch();
        if ((int) $result->ct <= 0) {
          return FALSE;
        }
      }
      catch (\Exception $e) {
        watchdog_exception('tg_news2_migration', $e);
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Pre flight requirements check (taxonomies populated).
   * @return bool
   */
  public static function checkRefdataPopulated($vocabs) {
    if (!empty($vocabs)) {
      foreach ($vocabs as $vid) {
        if (!self::getTerms($vid)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  // Other

  /**
   * Derive abbreviation from string.
   * @param $str
   * @param $length
   * @return string
   */
  public static function abbrev($str, $length = 100) {
    if (strlen($str) > $length) {
      $str = Unicode::truncate($str, $length, TRUE, TRUE);
    }
    return $str;
  }

  /**
   * Remove any "TOK" type string (e.g. myimage.jpg?xxxxx)
   * @param $filename
   * @return string
   */
  public static function deTok($filename) {
    $parts = explode('?', $filename);
    return $parts[0];
  }

  /**
   * Get relative file uri => file_create_url($target['uri']); -- didnt work in drush!
   * So, just hack it... replace public:// with appropriate
   * @param $uri
   * @param $public_folder_uri
   * @return string
   */
  public static function fileRelativeUri($uri, $public_folder_uri) {
    if (preg_match('#^public://#', $uri) && !empty($public_folder_uri)) {
      $public_folder_uri = rtrim($public_folder_uri, '/') . '/';
      $uri = str_replace('public://', $public_folder_uri, $uri);
    }
    return $uri;
  }

  /**
   * Returns file & pathname.
   * @param $filepath
   * @param $detok
   * @return array
   */
  public static function filePathAndName($filepath, $detok = TRUE) {
    $pathname = $filename = NULL;
    if (!empty($filepath)) {
      $parts = explode('/', $filepath);
      // last bit of path
      $filename = array_pop($parts);
      // remove "?" bit
      $filename = $detok ? self::deTok($filename) : $filename;
      //
      $pathname = implode('/', $parts);
    }
    return array($pathname, $filename);
  }

  /**
   * Get file via system retrieve file.
   * @param $source_uri
   * @param $target_uri
   * @param $target_file
   * @param $public_folder_uri
   * @return string
   */
  public static function getFile($source_uri, $target_uri, $target_file, $public_folder_uri = '/sites/default/files') {
    if (!file_exists($target_file)) {
      system_retrieve_file($source_uri, $target_uri); // unmanaged file.
    }
    return self::fileRelativeUri($target_uri, $public_folder_uri);
  }

  /**
   * Get terms from named vocabulary.
   * @param $vid
   * @return array | bool
   */
  public static function getTerms($vid) {
    try {
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadTree($vid);
      if (empty($terms)) {
        return FALSE;
      }
    }
    catch (InvalidPluginDefinitionException $e) {
      // should never happen!
      return FALSE;
    }
    return $terms;
  }

  /**
   * Get media file parts.
   * @param $file
   * @param $json
   * @param $source_domain
   * @param $source_public_folder_uri
   * @param $target_folder
   * @param $rename_by - prefix or subfolder
   * @return \stdClass
   */
  public static function mediaParts($file, $json, $source_domain, $source_public_folder_uri, $target_folder, $rename_by = 'prefix') {
    // derive filename from file object
    $subfolder = $file_prefix = '';
    if (empty($json->view_mode) || $json->view_mode == 'default') {
      $uri = $file->uri;
    }
    else {
      $uri = 'public://styles/' . $json->view_mode . '/public/' . $file->uri;
      if ($rename_by == 'prefix') {
        $file_prefix = $json->view_mode . '-';
      }
      elseif ($rename_by == 'subfolder') {
        $subfolder = self::niceFileName($json->view_mode) . '/';
      }
    }
    list($ignore, $filename) = self::filePathAndName($uri);
    $filename = self::niceFileName($filename);

    //
    $ret = new \stdClass();

    $ret->source_uri = $source_domain . self::fileRelativeUri($uri, $source_public_folder_uri);
    $ret->target_uri = $target_folder . $subfolder . $file_prefix . $filename;
    $ret->target_file = \Drupal::service('file_system')->realpath($ret->target_uri);
    $ret->subfolder = !empty($subfolder) ? $target_folder . $subfolder : NULL;

    // build the destination tag
    if ($file->type == 'image') {
      $ret->tag = '<img src="[:url]"';
      foreach ($json->attributes as $key => $value) {
        $ret->tag .= ' ' . $key . '="' . str_replace('"', '', $value) . '"';
      }
      $ret->tag .= ' />';
    }
    else {
      // @todo - other file types
      $ret->tag = NULL;
    }

    return $ret;
  }

  /**
   * Convert to nice name.
   * @param $filename
   * @return string
   */
  public static function niceFileName($filename) {
    $filename = str_replace(' ', '-', strtolower($filename));
    $filename = preg_replace('/[^\da-z\.\-_]/', '', $filename);
    //
    return $filename;
  }
}