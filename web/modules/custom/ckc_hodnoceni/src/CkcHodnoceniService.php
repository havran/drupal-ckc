<?php

namespace Drupal\ckc_hodnoceni;

use Drupal\views\Views;

class CkcHodnoceniService {

  const CKC_ROCNIK = 'rocnik';
  const CKC_KATEGORIE = 'kategorie';

  /**
   * Get years from taxonomy 'rocnik', ordered by 'name' (year).
   * Cached for fast access.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function get_years() {
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_ROCNIK;
    // Load cached years.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      //echo '-> Cache hit - get_years'.PHP_EOL;
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

    //echo '-> Cache miss - get_years'.PHP_EOL;
    return $term_data;
  }

  /**
   * Get categories from taxonomy 'lategorie', order by 'field_kod_kategorie'.
   * Also return clean names for using in URL.
   * Cached for fast access.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function get_categories() {
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_KATEGORIE;
    // Load cached categories.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      //echo '-> Cache hit - get_categories'.PHP_EOL;
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

    //echo '-> Cache miss - get_categories'.PHP_EOL;
    return $term_data;
  }

  public static function get_category_kod_by_name(string $category_name_clean) {
    $categories = self::get_categories();
    $kod = $categories[0]['kod'];
    foreach ($categories as $category) {
      if ($category['name_clean'] === $category_name_clean) {
        $kod = $category['kod'];
        break;
      }
    }
    return $kod;
  }

  /**
   * Get all works by year and category.
   * Cached for fast access.
   *
   * @param string $year
   * @param string $category
   * @return array
   */
  public static function get_works_by_year_and_category(string $year, string $category_code) {
    $cid = "ckc_hodnoceni:works:{$year}:{$category_code}";
    // Load cached works.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      return $cache->data;
    }

    // Get options from view and store them in cache.
    $works = [];
    $view = Views::getView('ckc_prace');
    $view->setArguments([$year, $category_code]);
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

  public static function kategorie($use_name_clean = false) {
    $kategorie = &drupal_static(__FUNCTION__, []);
    if (!empty($kategorie)) {
      return $kategorie;
    }
    $categories = self::get_categories();
    $kategorie = array_reduce($categories, function($acc, $item) use ($use_name_clean) {
      $acc[$item['kod']] = $use_name_clean ? $item['name_clean'] : $item['name'];
      return $acc;
    }, []);
    return $kategorie;
  }

  public static function prace($year, $category) {
    $prace = &drupal_static(__FUNCTION__, []);
    if (!empty($prace)) {
      return $prace;
    }
    // Get category code by 'name_clean' category name.
    $kategorie = self::kategorie(true);
    $category_code = array_flip($kategorie)[$category];
    $works = \Drupal::service('ckc_hodnoceni.service')->get_works_by_year_and_category($year, $category_code);
    $prace = array_reduce($works, function($acc, $item) {
      $acc[$item['kod']] = "{$item['kod']} {$item['title']}";
      return $acc;
    }, []);
    if (empty($prace)) {
      return ['' => ''];
    }
    return $prace;
  }
}
