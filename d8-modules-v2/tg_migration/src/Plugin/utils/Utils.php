<?php

namespace Drupal\tg_migration\Plugin\utils;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Utility\Unicode;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\ConnectionNotDefinedException;
use Drupal\Core\Database\Database;
use Drupal\migrate\Row;

/**
 * General utilities for migration.
 */
class Utils {

  // Requirements

  /**
   * Pre flight SQL requirements check (Source tables exist).
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
   * @param $vocabs
   * @return bool
   */
  public static function checkRefdataPopulated(array $vocabs) {
    if (!empty($vocabs)) {
      foreach ($vocabs as $vid) {
        if (!self::getTerms($vid)) {
          return FALSE;
        }
      }
    }
    return TRUE;
  }

  // Config

  /**
   * Initialise process plugin config. Basically a (much) simplified version of the
   * get functionality...
   * @param $config - by reference
   * @param \Drupal\migrate\Row $row
   * @return void
   */
  public static function initProcessConfig(array &$config, Row $row) {
    if (!empty($config)) {
      foreach ($config as $name => &$value) {
        switch (TRUE) {
          case self::isEmpty($value):
          case !is_string($value):
          case in_array($name, ['plugin', 'source']):
            break;
          case preg_match('/^@(.*)$/', $value, $matches):
            $property_name = $matches[1];
            if ($row->hasDestinationProperty($property_name)) {
              $value = $row->getDestinationProperty($property_name);
            }
            break;
          case preg_match('#^constants/(.*)$#', $value):
            $property_name = $value;
            if ($row->hasSourceProperty($property_name)) {
              $value = $row->getSourceProperty($property_name);
            }
            break;
          case preg_match('#^source/(.*)$#', $value, $matches):
            $property_name = $matches[1];
            if ($row->hasSourceProperty($property_name)) {
              $value = $row->getSourceProperty($property_name);
            }
            break;
          default:
        }
      }
    }
  }

  // Other

  /**
   * Derive abbreviation from string.
   * @param $str
   * @param $length
   * @param $elipse
   * @return string
   */
  public static function abbrev($str, $length = 100, $elipse = '') {
    if (strlen($str) > $length) {
      $str = Unicode::truncate($str, $length, TRUE, FALSE);
      $str .= $elipse;
    }
    return $str;
  }

  /**
   * Convert array to csv.
   * thanks -> http://php.net/manual/en/function.fputcsv.php#Vu74118
   * @param $values
   * @return string | null
   */
  public static function arrayToCSV(array $values) {
    $ret = NULL;
    if (!empty($values)) {
      // use fputcsv to generate the CSV data
      $csv = fopen('php://temp', 'rw');
      fputcsv($csv, $values);
      rewind($csv);
      $ret = trim(stream_get_contents($csv));
      //
      fclose($csv);
    }
    return $ret;
  }

  /**
   * Trim array elements.
   * @param $values
   * @return array
   */
  public static function arrayTrimElements(array $values) {
    $values = array_map( function($value) {
      return is_string($value) ? trim($value) : $value;
    }, $values);
    return $values;
  }

  /**
   * Build full pathname - from folder(s) + filename.
   * @param $folders
   * @param $filename
   * @return string
   */
  public static function buildPathName($folders, $filename = NULL) {
    $pathname = $delimiter = '';

    $folders = !is_array($folders) ? [$folders] : $folders;
    $folders[] = $filename;
    foreach ($folders as $folder) {
      if (!self::isEmpty($folder)) {
        $pathname .= !self::isEmpty($pathname) && !self::endsWith($pathname, $delimiter) ? $delimiter : '';
        $pathname .= ltrim($folder, $delimiter);
        $delimiter = '/';
      }
    }

    return $pathname;
  }

  /**
   * Delete named file
   * @param $uri
   * @return void
   */
  public static function deleteFile($uri) {
    \Drupal::service('file_system')->unlink($uri);
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
   * Check if string ends with characters.
   * @param $haystack
   * @param $needle
   * @return bool
   */
  public static function endsWith($haystack, $needle) {
    $len = strlen($needle);
    return $len == 0 || (substr($haystack,-$len) == $needle);
  }

  /**
   * Standard error message.
   * @param $id
   * @param $msg
   * @param $args
   * @return string
   */
  public static function errorMessage($id, $msg, array $args = []) {
    if (!empty($msg)) {
      $msg = '(@migration) ' . $msg;
      $args['@migration'] = $id;
    }
    return t($msg, $args);
  }

  /**
   * Check if file exists.
   * @param $uri
   * @return bool
   */
  public static function fileExists($uri) {
    if (!self::isEmpty($uri)) {
      $filepath = self::realFilePath($uri);
      return file_exists($filepath);
    }
    return FALSE;
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
   * Get database connection.
   * @param $key
   * @param string $target
   * @return \Drupal\Core\Database\Connection | bool
   */
  public static function getDatabase($key, $target = 'default') {
    try {
      $database = Database::getConnection($target, $key);
    }
    catch (ConnectionNotDefinedException $e) {
      self::logError($e->getMessage());
    }
    return $database;
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
   * Get file meta data. Doesnt get the title or alt text.
   * @todo: find the best method for this
   * @param $fid
   * @return array
   */
  public static function getFileMetaData($fid) {
    $ret = [];

    $database = Database::getConnection();

    $query = $database->select('file_metadata', 'f');
    $query->addField('f', 'name');
    $query->addField('f', 'value');
    $query->condition('f.fid', $fid);

    foreach ($query->execute() as $row) {
      $ret[$row->name] = $row->value;
    }

    return $ret;
  }

  /**
   * Get file object.
   * @param $fid
   * @return object | null
   */
  public static function getFileObject($fid) {
    $file_object = new \stdClass();
    // alternatively:
    // $file = \Drupal\file\Entity\File::load($fid) -- no throws
    try {
      $file_storage = \Drupal::entityTypeManager()->getStorage('file');
      if (!$file = $file_storage->load($fid)) {
        throw new \Exception();
      }
      $file_object->fid = $file->id();
      $file_object->uuid = $file->uuid();
      $file_object->filename = $file->get('filename')->value;
      $file_object->filemime = $file->get('filemime')->value;
      $file_object->filesize = $file->get('filesize')->value;
      $file_object->created = $file->get('created')->value;
      $file_object->changed = $file->get('changed')->value;
      $file_object->status = $file->get('status')->value;
      $file_object->type = $file->get('type')->target_id;
    }
    catch (\Exception $e) {
      // failed to get file object
      return NULL;
    }
    return $file_object;
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
   * Test is value is empty.
   * @param $value
   * @return bool
   */
  public static function isEmpty(&$value) {
    switch (TRUE) {
      case !isset($value):
        return TRUE;
      case is_string($value):
        return strlen( trim($value) ) == 0;
      case is_int($value):
        return FALSE;
      default:
        return empty($value);
    }
  }

  /**
   * Test is value is numeric.
   * @param $value
   * @return bool
   */
  public static function isNumeric(&$value) {
    return isset($value) && ctype_digit($value);
  }

  /**
   * Test if full url (http/https)
   * @param $uri
   * @return bool
   */
  public static function isFullUrl($uri) {
    return preg_match('#https?://#', $uri);
  }

  /**
   * Check if valid regex pattern. Allows for /, #, or ~ delimiters.
   * @param $regex
   * @return bool
   */
  public static function isValidRegex($regex) {
    $delim = !self::startsWith($regex, ['/', '#', '~']) ? '~' : '';
    return @preg_match($delim . $regex . $delim, NULL) !== FALSE;
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
      $uri = self::buildPathName(['public://styles/', $json->view_mode, '/public/'], $file->uri);
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

    $ret->source_uri = self::buildPathName($source_domain, self::fileRelativeUri($uri, $source_public_folder_uri));

    $ret->target_uri = self::buildPathName([$target_folder, $subfolder], $file_prefix . $filename);

    $ret->target_file = self::realFilePath($ret->target_uri);

    $ret->subfolder = !empty($subfolder) ? self::buildPathName([$target_folder, $subfolder]) : NULL;

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
   * Log error message.
   * @param $msg
   */
  public static function logError($msg) {
    // set message
    drupal_set_message($msg, 'error', TRUE);

    // log it!
    \Drupal::logger('tg_news_csv_migration')->error($msg);
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

  /**
   * Returns plaintext version of supplied string.
   * thanks: https://www.texelate.co.uk/blog/get-plain-text-intro-from-html-using-php
   * @param $value
   * @return string
   */
  public static function plainText($value) {
    if (is_string($value) && !self::isEmpty($value)) {
      $value = strip_tags($value);
      $value = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
      $value = str_replace(PHP_EOL, ' ', $value);
    }
    return $value;
  }

  /**
   * Returns real (linux) file path.
   * @param $uri
   * @return string | null
   */
  public static function realFilePath($uri) {
    return !self::isEmpty($uri) ? \Drupal::service('file_system')->realpath($uri) : NULL;
  }
  /**
   * Abbreviate the supplied filename.
   * @param $filename
   * @param $maxlength
   * @return string
   */
  public static function shortFileName($filename, $maxlength = 60) {
    if (!self::isEmpty($filename) && $maxlength > 0) {
      $parts = pathinfo($filename);
      if (strlen($parts['filename']) > $maxlength) {
        $filename = $parts['filename'];
        $extension = !self::isEmpty($parts['extension']) ? '.' . $parts['extension'] : '';
        // convert to hashvalue
        $hash = MD5($parts['filename']);
        $hashlen = strlen($hash);
        if ($hashlen > ($maxlength - 1)) {
          $filename = $hash;
        }
        else {
          $len = $maxlength - $hashlen - 1;
          $filename = substr($filename, 0, $len) . '-' . $hash;
        }
        $filename = $filename . $extension;
        $filename = !self::isEmpty($parts['dirname']) ? self::buildPathName($parts['dirname'], $filename) : $filename;
      }
    }
    return $filename;
  }

  /**
   * Check if string ends with characters.
   * @param $haystack
   * @param $needle
   * @return bool
   */
  public static function startsWith($haystack, $needle) {
    $needles = !is_array($needle) ? [$needle] : $needle;
    foreach ($needles as $needle) {
      $len = strlen($needle);
      if ($len > 0 && substr($haystack, 0, $len) == $needle) {
        return TRUE;
      }
    }
    return FALSE;
  }
}
