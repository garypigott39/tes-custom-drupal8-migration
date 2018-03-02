<?php

namespace Drupal\tg_news2_migration\Plugin\migrate;

class D7Tags {

  /**
   * Derive term id from supplied "title".
   * @param $name
   * @param $vocabularies
   * @return int | bool
   */
  public static function getTid($name, array $vocabularies = []) {
    foreach ($vocabularies as $vid) {
      if ($terms = taxonomy_term_load_multiple_by_name($name, $vid)) {
        $tids = array_keys($terms);
        // if multiple, then ideally want the "GB" version
        if (count($terms) > 1) {
          foreach ($tids as $tid) {
            // thanks -> http://purencool.com/accessing-taxonomys-name-and-parent-tid-in-drupal-8
            $storage = \Drupal::service('entity_type.manager')
              ->getStorage('taxonomy_term');
            if ($parents = $storage->loadParents($tid)) {
              foreach ($parents as $term) {
                $name = $term->getName();  // parent tid = $term->id();
                if ($name == 'GB') {
                  return $tid;
                }
              }
            }
          }
        }
        return $tids[0];
      }
    }
    return FALSE;
  }

  /**
   * Get term id from xref (loaded vocabularies).
   * @param $name
   * @param $configuration
   * @return bool|int
   */
  public static function getXref($name, $configuration) {
    $name = self::tidyName($name);
    $vocabularies = self::vocabularies($configuration);

    return self::getTid($name, $vocabularies);
  }

  /**
   * Minor tidy up of name.
   * @param $name
   * @return string
   */
  public static function tidyName($name) {
    $maps = [
      'Academies News' => 'Academies',
      'Alternative provision focus' => 'Alternative provision',
      'Behavior' => 'Behaviour',
      'Book review' => 'Book reviews',
      'Business and financial management' => 'Business, finance and economics',
      'Ed tech' => 'EdTech',
      'Revision Tips' => 'Revision tips',
      'Local authority' => 'Local government',
      'Local authorities' => 'Local government',
      'religion' => 'Religion',
    ];

    if (isset($maps[$name])) {
      $name = $maps[$name];
    }
    elseif (preg_match('/^TES /', $name)) {
      $name = 'Tes ' . substr($name, 4);
    }

    return $name;
  }

  /**
   * Gets array of vocabularies from config settings.
   * @param $configuration
   * @return array
   */
  public static function vocabularies($configuration) {
    $ret = [];
    if (!empty($configuration['tags_xref'])) {
      $ret = $configuration['tags_xref'];
    }
    return is_array($ret) ? $ret : [$ret];
  }
}

