<?php

namespace Drupal\tg_news_migration\Plugin\migrate;

use Drupal\migrate\Row;
use Drupal\Component\Utility\Unicode;

class HelperUtils {

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
   * Extract filenames.
   * @param \Drupal\migrate\Row $row
   * @param $fieldname
   * @param $prefix
   * @return array
   */
  public static function getAbsoluteFilenames(Row $row, $fieldname) {
    $ret = $row->hasSourceProperty($fieldname)
      ? explode(', ', $row->getSourceProperty($fieldname))
      : [];
    if (!empty($ret)) {
      $ret = array_map(function ($itm) {
        // strip off domain
        if (preg_match('/^.*(\/sites\/.*)$/', $itm, $matches)) {
          $itm = $matches[1];
        }
        return $itm;
      }, $ret);
    }

    return $ret;
  }

  /**
   * Get relative file uri.
   * @param $uri
   * @param $public_folder_uri
   * @return string
   */
  public static function getFileRelativeUrl($uri, $public_folder_uri) {
    // just hack it... replace public:// with appropriate
    if (preg_match('#^public://#', $uri) && !empty($public_folder_uri)) {
      $public_folder_uri = rtrim($public_folder_uri, '/') . '/';
      $uri = str_replace('public://', $public_folder_uri, $uri);
    }
    return $uri;
  }

  /**
   * Get file & pathname.
   * @param $filepath
   * @param $detok
   * @return array
   */
  public static function getPathAndFileName($filepath, $detok = TRUE) {
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
   * Extract & prefix array elements.
   * @param \Drupal\migrate\Row $row
   * @param $fieldname
   * @param $prefix
   * @return array
   */
  public static function getTags(Row $row, $fieldname, $prefix = NULL) {
    $ret = $row->hasSourceProperty($fieldname)
      ? explode(', ', $row->getSourceProperty($fieldname))
      : [];
    if (!empty($prefix)) {
      $ret = array_map(function ($itm) use ($prefix) {
        return $prefix . self::tagsXref($itm);
      }, $ret);
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

  /**
   * Map tags title - @todo: rules
   * @param $title
   * @return string
   */
  public static function tagsXref($title) {
    $maps = [
      'Academies News' => 'Academies',
      'Alternative provision focus' => 'Alternative provision',
      'Behavior' => 'Behaviour',
      'Business and financial management' => 'Business, finance and economics',
      'Revision Tips' => 'Revision tips',
      'Local authority' => 'Local government',
      'Local authorities' => 'Local government',
    ];
    if (isset($maps[$title])) {
      $title = $maps[$title];
    }
    return $title;
  }
}
