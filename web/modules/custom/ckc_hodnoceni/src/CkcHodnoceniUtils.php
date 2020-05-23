<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\CkcHodnoceniUtils.
 */

namespace Drupal\ckc_hodnoceni;

use Drupal\Core\Database\Database;
use Drupal\user\Entity\User;

trait CkcHodnoceniUtils {

  private function get($var, string $path = '', $default = null) {
    if (empty($var) || is_array($var) === false) {
      return $default;
    }
    if (empty($path)) {
      return $var;
    }
    $path_segments = explode('.', $path);
    $segment = array_shift($path_segments);
    $val = empty($var[$segment]) ? $default : $var[$segment];
    if (count($path_segments) === 0) {
      return $val;
    }
    if (count($path_segments) > 0 && is_array($val)) {
      return $this->get($val, join('.', $path_segments), $default);
    }
    return $default;
  }

  private function readRateRecords(string $ckc_year) {
    $connection = Database::getConnection();
    $query = $connection->select('ckc_hodnoceni', 'h')
      ->fields('h')
      ->condition('ckc_year', $ckc_year)
      ->condition('status', 1);
    $results = $query->execute();
    $rateRecords = [];
    while ($result_record = $results->fetchAssoc()) {
      // base data
      $username = User::load($result_record['uid'])->getUsername();
      $rateRecords[$result_record['rid']] = [
        '_ckc_year' => $ckc_year,
        '_category_id' => $result_record['ckc_category'],
        '_uid' => $result_record['uid'],
        '_name_clean' => \Drupal::service('pathauto.alias_cleaner')->cleanString($username),
        'category' => 'Kategorie: '. CkcHodnoceniService::categories()[$result_record['ckc_category']] .' ('. $result_record['ckc_category'] .')',
        'name' => 'Jméno porotce: '. $username .' ('. $result_record['uid'] .')',
        'note' => 'Poznámky: '. trim($result_record['note']),
      ];
      // prepare places
      $places_raw = $this->readWorksRatesForRateRecord($result_record);
      $places = [];
      foreach ($places_raw as $work_id => $place) {
        if (empty($place['inputName'])) {
          continue;
        }
        $places[$place['inputName']] = $work_id;
      }
      // places
      foreach (CkcHodnoceniBase::CKC_HODNOCENI_TABLE_FIELDS_PLACES as $place) {
        $rateRecords[$result_record['rid']][$place] = empty($places[$place]) ? '---' : $places[$place];
      }
    }
    return $rateRecords;
  }

  private function readRateRecord(string $ckc_year, string $ckc_category, int $uid) {
    $connection = Database::getConnection();
    $query = $connection->select('ckc_hodnoceni', 'h')
      ->fields('h')
      ->condition('ckc_year', $ckc_year)
      ->condition('ckc_category', $ckc_category)
      ->condition('uid', $uid)
      ->range(0, 1);
    $result = $query->execute();
    $result_record = $result->fetchAssoc();
    return [
      'data' => $result_record,
      'data_works' => $this->readWorksRatesForRateRecord($result_record),
    ];
  }

  private function readWorksRatesForRateRecord($result_record) {
    $rid = $this->get($result_record, 'rid', false);
    if ($rid === false) {
      return [];
    }
    $connection = Database::getConnection();
    $query = $connection->select('ckc_hodnoceni_works', 'hw')
      ->fields('hw', ['work_id', 'work_place', 'work_place_order', 'work_mlok'])
      ->condition('rid', (int) $rid);
    $results = $query->execute();
    $data_works = [];
    foreach ($results->fetchAllAssoc('work_id', \PDO::FETCH_ASSOC) as $result_work) {
      if (empty($result_work['work_place'])) {
        $data_works[$result_work['work_id']] = [
          'inputName' => '',
          'mlok' => false,
        ];
        continue;
      }
      $data_works[$result_work['work_id']] = [
        'inputName' => "order_{$result_work['work_place']}_{$result_work['work_place_order']}",
        'mlok' => (bool) $result_work['work_mlok'],
      ];
    }
    return $data_works;
  }

}
