<?php
/**
 * @file
 * Drush integration for TES V2 Data.
 *
 * Drush one-off populate named entity queue.
 */

/**
 * Implements hook_drush_command().
 */
function tes_v2_data_drush_command() {
  $items = array();
  $items['tes_v2_data-pop'] = array(
    'description' => 'Populate tes_v2_news_nodes entity queue',
    'aliases' => array('v2-nodes'),
    'callback' => '_tes_v2_data_populate_queue',
  );
  $items['tes_v2_data_files'] = array(
    'description' => 'Extract files - tes_v2_news_nodes entity queue',
    'aliases' => array('v2-files'),
    'callback' => '_tes_v2_data_extract_files',
  );
  $items['tes_v2_authors'] = array(
    'description' => 'Extract authors - tes_v2_news_nodes entity queue',
    'aliases' => array('v2-authors'),
    'callback' => '_tes_v2_data_extract_authors',
  );
  return $items;
}

// Local callbacks


/**
 * Extract NIDs from entity queue.
 * @return array
 */
function _tes_v2_data_get_queue_nids() {
  $nids = array();
  //
  $queue_name = 'tes_v2_test_articles';
  //
  $field_name = 'eq_node';  // see above
  //
  try {
    if (!$queue = entityqueue_queue_load($queue_name)) {
      throw new Exception("Entity queue '$queue_name' does not exist");
    }
    if (!$subqueue = entityqueue_subqueue_load($queue->name)) {
      throw new Exception("Unable to load subqueue for '$queue_name'");
    }
    $nids = array_column($subqueue->{$field_name}[LANGUAGE_NONE], 'target_id');
  }
  catch (Exception $e) {
    $msg = $e->getMessage();
    watchdog('tes_v2_data', 'Error in v2 data (drush) extract, @message', array('@message' => $msg));
    drush_set_error(dt('Drush extract fails - @message', array('@message' => $msg)));
  }
  return $nids;
}

/**
 * Populate entity queue.
 * Thanks https://gist.github.com/bmunslow/5d41d00c81313d485a148e960d7615fa and https://drupal.stackexchange.com/questions/185637/auto-add-nodes-on-save-to-a-queue-using-entityqueue
 * @return void
 */
function _tes_v2_data_populate_queue() {
  $queue_name = 'tes_v2_test_articles';
  //
  try {
    if (!$queue = entityqueue_queue_load($queue_name)) {
      throw new Exception("Entity queue '$queue_name' does not exist");
    }
    if (!$subqueue = entityqueue_subqueue_load($queue->name)) {
      throw new Exception("Unable to load subqueue for '$queue_name'");
    }
    //==========================================================================
    // its a NODE based queue - so need NID and target field is 'EQ_NODE'
    // Get name of field which stores reference entities
    //==========================================================================
    $field_name = 'eq_node';  // _entityqueue_get_target_field_name($queue->target_type);

    // and the setings
    $bundles = isset($queue->settings['target_bundles'])
      ? $queue->settings['target_bundles'] : array('news_article');
    $number = !empty($queue->settings['max_size'])
      ? (int) $queue->settings['max_size'] : 100;

    // get list of nodes
    $ret = array();
    $query = db_select('node', 'n');
    $query->addField('n', 'nid');
    $query->condition('n.type', $bundles);
    $query->condition('n.status', 1);
    $query->condition('n.created', strtotime('-1 year'), '>=');
    $query->orderRandom();
    $query->range(0, $number);
    foreach ($query->execute() as $row) {
      $ret[] = array('target_id' => $row->nid);
    }

    // set & save subqueue
    $subqueue->{$field_name}[LANGUAGE_NONE] = $ret;
    entityqueue_subqueue_save($subqueue);
  }
  catch (Exception $e) {
    $msg = $e->getMessage();
    watchdog('tes_v2_data', 'Error in v2 data (drush) batch update, @message', array('@message' => $msg));
    drush_set_error( dt('Drush update fails - @message', array('@message' => $msg)) );
  }
}

/**
 * Extract authors. Uses the entity queue populated by the function _tes_v2_data_populate_queue.
 * @return void
 */
function _tes_v2_data_extract_authors() {
  try {
    // open file
    $directory = file_directory_temp();
    $filename = $directory . '/tes_v2_authors.csv';
    $outfile = fopen($filename, 'w');
    //
    $nids = _tes_v2_data_get_queue_nids();
    $authors[] = array('nid', 'title', 'body', 'photo');
    //
    $query = db_select('node', 'n');
    // want the author (news byline)
    $query->join(
        'field_data_field_news_article_byline', 'a',
      'n.nid = a.entity_id AND a.entity_type = :type AND a.bundle = :bundle AND a.deleted = 0',
              array(':type' => 'node', ':bundle' => 'news_article')
    );
    $query->addField('a', 'field_news_article_byline_target_id', 'nid');
    $query->condition('n.nid', $nids, 'IN');
    $query->orderBy('n.nid');
    foreach ($query->execute() as $row) {
      if ($node = node_load($row->nid)) {
        $authors[$row->nid] = array(
          'nid' => $node->nid,
          'title' => $node->title,
          'body' => !empty($node->body[LANGUAGE_NONE][0]['value']) ? $node->body[LANGUAGE_NONE][0]['value'] : NULL,
          'photo' => !empty($node->field_byline_photo[LANGUAGE_NONE]) ? file_create_url($node->field_byline_photo[LANGUAGE_NONE][0]['uri']) : NULL,
        );
      }
    }
    // write it!
    foreach ($authors as $row) {
      fputcsv($outfile, $row);
    }
    fclose($outfile);
  }
  catch (Exception $e) {
    $msg = $e->getMessage();
    watchdog('tes_v2_data', 'Error in v2 data (drush) authors extract, @message', array('@message' => $msg));
    drush_set_error(dt('Drush extract fails - @message', array('@message' => $msg)));
  }

}

/**
 * Extract files. Uses the entity queue populated by the function _tes_v2_data_populate_queue.
 * @return void
 */
function _tes_v2_data_extract_files() {
  try {
    // open file
    $directory = file_directory_temp();
    $filename = $directory . '/tes_v2_data_files.csv';
    $outfile = fopen($filename, 'w');
    //
    $nids = _tes_v2_data_get_queue_nids();
    $files[] = array('nid', 'type', 'path');
    //
    $query = db_select('node', 'n');
    $query->addField('n', 'nid');
    $query->condition('n.nid', $nids, 'IN');
    $query->orderBy('n.nid');
    foreach ($query->execute() as $row) {
      if ($node = node_load($row->nid)) {
        // hero image OR teaser image -- only should be 1
        if (!empty($node->field_news_article_teaser_image[LANGUAGE_NONE])) {
          $files[] = array(
            'nid' => $node->nid,
            'type' => 'teaser image',
            'path' => file_create_url($node->field_news_article_teaser_image[LANGUAGE_NONE][0]['uri']),
          );
        }
        elseif (!empty($node->field_news_article_images[LANGUAGE_NONE])) {
          $files[] = array(
            'nid' => $node->nid,
            'type' => 'hero image',
            'path' => file_create_url($node->field_news_article_images[LANGUAGE_NONE][0]['uri']),
          );
        }
        // attached files
        if (!empty($node->field_news_article_files[LANGUAGE_NONE])) {
          foreach ($node->field_news_article_files[LANGUAGE_NONE] as $item) {
            $files[] = array(
              'nid' => $node->nid,
              'type' => 'attachment',
              'path' => file_create_url($item['uri']),
            );
          }
        }
        /* -- ignore embedded files for now --
        // body files - images src=...etc
        if ($unmanaged_files = _tes_v2_data_embedded_files($node->body[LANGUAGE_NONE][0]['safe_value'])) {
          foreach ($unmanaged_files as $name) {
            $files[] = array(
              'nid' => $node->nid,
              'type' => 'unmanaged',
              'path' => file_create_url($name),
            );
          }
        }
        -- ignored -- */
      }
    }
    // write it!
    foreach ($files as $lineno => $row) {
      if ($lineno > 0) {
        $row['path'] = _tes_v2_data_absolute_path($row['path']);
      }
      fputcsv($outfile, $row);
    }
    fclose($outfile);
  }
  catch (Exception $e) {
    $msg = $e->getMessage();
    watchdog('tes_v2_data', 'Error in v2 data (drush) files extract, @message', array('@message' => $msg));
    drush_set_error(dt('Drush extract fails - @message', array('@message' => $msg)));
  }

}

/**
 * Returns absolute file path - essentially a copy of _image_url_formatter_get_absolute_file_url
 * @param $url
 * @return string
 */
function _tes_v2_data_absolute_path($url) {
  global $base_url;
  if (strpos($url, $base_url) === 0) {
    $url = base_path() . ltrim(str_replace($GLOBALS['base_url'], '', $url), '/');
  }
  return $url;
}

/**
 * Extracts list of embedded images, documents, etc.
 * -> thanks: https://stackoverflow.com/questions/15324397/extract-img-src-from-string-with-preg-match-all#answer-15324614
 * @param $html
 * @return array
 */
function _tes_v2_data_embedded_files($html) {
  $files = array();
  if (preg_match('/\/sites\/default\/files/', $html)) {
    $doc = new DOMDocument;
    $doc->loadHTML($html);
    $xpath = new DOMXPath($doc);

    if ($images = $xpath->query("//img[contains(@src, '/sites/default/files')]")) {
      foreach ($images as $file) {
        $uri = $file->getAttribute('src');
        if (preg_match('/\/sites\/default\/files/', $uri)) {
          // could also match for valid file types perhaps
          $files[] = $uri;
        }
      }
    }

    if ($other  = $xpath->query("//a[contains(@href, '/sites/default/files')]")) {
      foreach ($other as $file) {
        $uri = $file->getAttribute('href');
        if (preg_match('/\/sites\/default\/files/', $uri)) {
          // could match for valid file types perhaps
          $files[] = $uri;
        }
      }
    }
  }
  return $files;
}
