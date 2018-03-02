<?php

namespace Drupal\tg_news_migration\Plugin\migrate;

class InlineFiles {

  /**
   * Parses inline files --> attempts to change to new format name.
   * @todo: Would have liked to use DOMDocument parsing however the subsequent saveHtml screws up the content - from trial & error!!
   * See https://drupal.stackexchange.com/questions/24736/how-to-parse-out-links-and-img-src-references-from-body-copy for details of using Dom Document
   *
   * Thanks also to migrate_process_inline_files contrib module.
   *
   * @param $content
   * @param $source_domain
   * @param $target_folder
   * @return array
   */
  public static function parseFiles($content, $source_domain = 'https://tes.com', $target_folder = 'public://') {
    $files = [];
    // image and link files
    if (preg_match_all('/<(a|img) [^>]*>/i', $content, $matches)) {
      foreach ($matches[1] as $key => $tag) {
        if ($file = self::parseTagForFile($tag, $matches[0][$key])) {
          list($source_uri, $target_uri, $target_file) =
            self::getSourceAndTarget($file, $source_domain, $target_folder);
          $files[$source_uri] = [
            'file' => $target_file,
            'uri' => $target_uri,
          ];
        }
      }
    }

    return $files;
  }

  /**
   * Extract filename from image/anchor fragment.
   * @param $tag
   * @param $fragment
   * @return string | null
   */
  private static function parseTagForFile($tag, $fragment) {
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

  /**
   * Get source & target file names.
   * @param $uri
   * @param $source_domain
   * @param $target_folder
   * @return array
   */
  private static function getSourceAndTarget($uri, $source_domain, $target_folder) {
    // assumes URI is valid
    $source_url = (!preg_match('/https?:\/\//', $uri) ? $source_domain : '') . $uri;

    list($ignore, $filename) = HelperUtils::getPathAndFileName($uri);
    $filename = HelperUtils::niceFileName($filename);

    // make sure target folder ends with a "/"
    $target_folder .= !preg_match('#/$#', $target_folder) ? '/' : '';

    $target_uri = $target_folder . $filename;
    $target_filepath = \Drupal::service('file_system')->realpath($target_uri);

    return array($source_url, $target_uri, $target_filepath);
  }

}
