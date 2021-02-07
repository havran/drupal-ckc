<?php

namespace Drupal\ckc_hodnoceni;

use Drupal\views\Views;

class CkcHodnoceniService {

  const CKC_YEAR = 'rocnik';
  const CKC_CATEGORY = 'kategorie';

  /**
   * Get years from taxonomy 'rocnik', ordered by 'name' (year).
   * Cached for fast access.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function get_years() {
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_YEAR;
    // Load cached years.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      //echo '-> Cache hit - get_years'.PHP_EOL;
      return $cache->data;
    }

    $term_etm = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $terms_tids = $term_etm->getQuery()
      ->condition('vid', self::CKC_YEAR)
      ->condition('status', 1)
      ->execute();
    $terms = $term_etm->loadMultiple($terms_tids);
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[] = array(
        'id' => $term->tid->value,
        'name' => $term->name->value,
        'year' => $term->field_year->value,
        'locked' => $term->field_locked->value || false,
        'deadline' => $term->field_uzaverka->value,
      );
    }
    usort($term_data, function($a, $b) { return strcmp($a["name"], $b["name"]); });
    \Drupal::cache()->set($cid, $term_data);

    //echo '-> Cache miss - get_years'.PHP_EOL;
    return $term_data;
  }

  /**
   * Return map from year name to taxonomy_term id.
   *
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function year_map($key = 'name') {
    return array_reduce(
      self::get_years(),
      function ($acc, $i) use ($key) {
        $acc[$i[$key]] = $i;
        return $acc;
      },
      []
    );
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
    $cid = "ckc_hodnoceni:taxonomy:" . self::CKC_CATEGORY;
    // Load cached categories.
    if ($cache = \Drupal::cache()->get($cid, FALSE)) {
      //echo '-> Cache hit - get_categories'.PHP_EOL;
      return $cache->data;
    }

    $term_etm = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $terms_tids = $term_etm->getQuery()
      ->condition('vid', self::CKC_CATEGORY)
      ->condition('status', 1)
      ->execute();
    $terms = $term_etm->loadMultiple($terms_tids);
    $term_data = [];
    foreach ($terms as $term) {
      $term_data[] = array(
        'id' => $term->tid->value,
        'code' => $term->field_kod_kategorie->value,
        'name' => $term->name->value,
        'name_clean' => \Drupal::service('pathauto.alias_cleaner')->cleanString($term->name->value),
      );
    }
    usort($term_data, function($a, $b) { return strcmp($a["code"], $b["code"]); });
    \Drupal::cache()->set($cid, $term_data);

    //echo '-> Cache miss - get_categories'.PHP_EOL;
    return $term_data;
  }

  /**
   *  Return map from category code to taxonomy_term id.
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function category_map() {
    return  array_reduce(
      self::get_categories(),
      function ($acc, $i) {
        $acc[$i['code']] = $i['id'];
        return $acc;
      },
      []
    );
  }

  /**
   * @param string $category_name_clean
   * @return mixed|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function get_category_code_by_name(string $category_name_clean) {
    $categories = self::get_categories();
    $code = '';
    foreach ($categories as $category) {
      if ($category['name_clean'] === $category_name_clean) {
        $code = $category['code'];
        break;
      }
    }
    return $code;
  }

  /**
   * Get all works by year and category.
   * Cached for fast access.
   *
   * @param string $year
   * @param string $category
   * @return array
   */
  public static function get_works_by_year_and_category(string $year, string $category) {
    $categories = self::categories(true);
    $category_code = in_array($category, $categories, TRUE)
      ? array_flip($categories)[$category]
      : $category;

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
      $year = trim(strip_tags($view->style_plugin->getField($id, 'field_rocnik_ref')));
      $category_code = trim(strip_tags($view->style_plugin->getField($id, 'field_kod_kategorie')));
      $order = trim(strip_tags($view->style_plugin->getField($id, 'field_poradi_povidky')));
      $title = trim(strip_tags($view->style_plugin->getField($id, 'title')));

      $works[] = [
        'code' => $category_code . $order,
        'year' => $year,
        'category' => $category_code,
        'title' => $title,
      ];
    }
    \Drupal::cache()->set($cid, $works);

    return $works;
  }

  /**
   * Get array of categories. If $use_name_clean === TRUE, then return categories without punctuation.
   *
   * @param bool $use_name_clean
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function categories($use_name_clean = false) {
    $categories = &drupal_static(__FUNCTION__, []);
    $key = $use_name_clean ? 'clean' : 'normal';
    if (!empty($categories) && isset($categories[$key])) {
      return $categories[$key];
    }
    $categories = self::get_categories();
    $categories = array_reduce($categories, function($acc, $item) use ($use_name_clean) {
      $acc['clean'][$item['code']] = $item['name_clean'];
      $acc['normal'][$item['code']] = $item['name'];
      return $acc;
    }, []);
    return $categories[$key];
  }

  /**
   * Get array of works by $year and $category. Category can be namr (from router) or number (as string).
   *
   * @param $year
   * @param $category
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function works($year, $category, $no_id = false) {
    $categories = self::categories(true);
    $category_code = in_array($category, $categories, TRUE)
      ? array_flip($categories)[$category]
      : $category;
    $works = &drupal_static(__FUNCTION__, []);
    if (!empty($works) && isset($works["{$year}:{$category_code}"])) {
      return $works["{$year}:{$category_code}"];
    }
    $works["{$year}:{$category_code}"] = array_reduce(
      self::get_works_by_year_and_category($year, $category_code),
      function($acc, $item) use ($no_id) {
        $acc[$item['code']] = $no_id ? $item['title'] : "{$item['code']} {$item['title']}";
        return $acc;
      },
      []
    );
    return $works["{$year}:{$category_code}"];
  }

  public static function validate_year(string $year) {

  }

  public static function validate_category_number(string $number) {
    return in_array($number, array_keys(self::categories()), TRUE);
  }

  public static function validate_category_string(string $category) {
    return in_array($category, self::categories(true), TRUE);
  }

  /**
   * Get years from taxonomy 'rocnik', ordered by 'name' (year).
   * Cached for fast access.
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function set_active_year(int $year_id) {
    $term_etm = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term');
    $terms_tids = $term_etm->getQuery()
      ->condition('vid', self::CKC_YEAR)
      ->execute();
    $terms = $term_etm->loadMultiple($terms_tids);
    foreach ($terms as $term) {
      if ((string) $year_id === (string) $term->tid->value) {
        $term->field_locked->setValue(FALSE);
      } else {
        $term->field_locked->setValue(TRUE);
      }
      $term->save();
    }
  }

  public static function active(string $year_from_url) {
    $years = CkcHodnoceniService::year_map();
    $year_active = (string) \Drupal::configFactory()->getEditable('ckc_hodnoceni.settings')->get('year_active');
    return $year_active === $years[$year_from_url]['id'];
  }

}
