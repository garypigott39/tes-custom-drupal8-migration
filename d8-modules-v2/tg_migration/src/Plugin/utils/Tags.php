<?php

namespace Drupal\tg_migration\Plugin\utils;

/**
 * Tag utilities for migration.
 */
class Tags {

  /**
   * Get term id from xref (loaded vocabularies).
   * @param $name
   * @param $vocabs
   * @return bool|int
   */
  public static function getXref($name, $vocabs) {
    if (empty($vocabs)) {
      return NULL;
    }
    $name = self::tidyName($name);
    $vocabs = is_array($vocabs) ? $vocabs : [$vocabs];

    return self::getTid($name, $vocabs);
  }

  /**
   * Minor tidy up of name.
   * @param $name
   * @return string
   */
  public static function tidyName($name) {
    if (!Utils::isEmpty($name)) {
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
        'Ofsted blog' => 'Ofsted',
        'Ofsted watch' => 'Ofsted',
        'PE' => 'Physical education',
        'RE' => 'Religious education',
        'PSHE' => 'Personal, social and health education',
        'religion' => 'Religion',
      ];
      // CSV hacks: issues experienced in CSV version where tagname explode didnt work,
      // e.g. Business, finance and economics => gives 2 tagnames
      $maps['finance and economics'] = 'Business, finance and economics';
      //
      if (isset($maps[$name])) {
        $name = $maps[$name];
      }
      elseif (preg_match('/^TES /', $name)) {
        $name = 'Tes ' . substr($name, 4);
      }
    }
    return $name;
  }

  // Private methods

  /**
   * Derive term id from supplied "title".
   * @param $name
   * @param $vocabularies
   * @return int | bool
   */
  private static function getTid($name, array $vocabularies = []) {
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
}

