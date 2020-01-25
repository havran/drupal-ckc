<?php

namespace Drupal\ckc_hodnoceni;

use Drupal\views\Views;

class CkcHodnoceniService {

  const CKC_ROCNIK = 'rocnik';
  const CKC_KATEGORIE = 'kategorie';

  public static function get_years() {
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_ROCNIK;
    // Load cached years.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      echo '-> Cache hit - get_years'.PHP_EOL;
      return $cache->data;
    }

    $term_etm = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $terms_tids = $term_etm->getQuery()
      ->condition('vid', self::CKC_ROCNIK)
      ->condition('status', 1)
      ->execute();
    $terms = $term_etm->loadMultiple($terms_tids);
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[] = array(
        'id' => $term->tid->value,
        'name' => $term->name->value,
      );
    }
    usort($term_data, function($a, $b) { return strcmp($a["name"], $b["name"]); });
    \Drupal::cache()->set($cid, $term_data);

    echo '-> Cache miss - get_years'.PHP_EOL;
    return $term_data;
  }

  public static function get_categories() {
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_KATEGORIE;
    // Load cached categories.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      echo '-> Cache hit - get_categories'.PHP_EOL;
      return $cache->data;
    }

    $term_etm = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $terms_tids = $term_etm->getQuery()
      ->condition('vid', self::CKC_KATEGORIE)
      ->condition('status', 1)
      ->execute();
    $terms = $term_etm->loadMultiple($terms_tids);
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[] = array(
        'id' => $term->tid->value,
        'kod' => $term->field_kod_kategorie->value,
        'name' => $term->name->value,
        'name_clean' => \Drupal::service('pathauto.alias_cleaner')->cleanString($term->name->value),
      );
    }
    usort($term_data, function($a, $b) { return strcmp($a["kod"], $b["kod"]); });
    \Drupal::cache()->set($cid, $term_data);

    echo '-> Cache miss - get_categories'.PHP_EOL;
    return $term_data;
  }

  public static function get_works_by_year_and_category(string $year, string $category) {
    $cid = "ckc_hodnoceni:works:{$year}:{$category}";
    // Load cached works.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      return $cache->data;
    }

    // Get options from view and store them in cache.
    $works = [];
    $view = Views::getView('ckc_prace');
    $view->setArguments([$year, $category]);
    $view->execute();
    foreach ($view->result as $id => $row) {
      $rocnik = trim(strip_tags($view->style_plugin->getField($id, 'field_rocnik_ref')));
      $kategorie_kod = trim(strip_tags($view->style_plugin->getField($id, 'field_kod_kategorie')));
      $poradi = trim(strip_tags($view->style_plugin->getField($id, 'field_poradi_povidky')));
      $title = trim(strip_tags($view->style_plugin->getField($id, 'title')));

      $works[] = [
        'kod' => $kategorie_kod . $poradi,
        'rocnik' => $rocnik,
        'kategorie' => $kategorie_kod,
        'title' => $title,
      ];
    }
    \Drupal::cache()->set($cid, $works);

    return $works;
  }

}
