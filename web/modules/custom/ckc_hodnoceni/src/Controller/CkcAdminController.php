<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcAdminController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\ckc_hodnoceni\CkcHodnoceniDB;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Drupal\views\Views;

class CkcAdminController extends ControllerBase {

  public function main() {
    return [
      '#markup' => '<p>zoznam...</p>',
    ];
  }

  public function main_title() {
    return "CKČ administrace";
  }

  public function status(string $ckc_rocnik) {
    $ckc_rocnik = empty($ckc_rocnik) ? $this->default_year() : $ckc_rocnik;
    $view = [
      '#type' => 'view',
      '#name' => 'ckc_hlasovani',
      '#display_id' => 'default',
      '#arguments' => [
        $ckc_rocnik,
      ],
    ];
    return [
      'data' => $view,
    ];
  }

  public function status_title(string $ckc_rocnik) {
    $ckc_rocnik = empty($ckc_rocnik) ? $this->default_year() :$ckc_rocnik;
    return "CKČ {$ckc_rocnik} - stav hlasování";
  }

  public function status_switch(string $ckc_rocnik, int $rid) {
    CkcHodnoceniDB::rate_status_switch($rid);
    $view = Views::getView('ckc_hlasovani');
    $view->storage->invalidateCaches();
    return $this->redirect('ckc_hodnoceni.admin.status', ['ckc_rocnik' => $ckc_rocnik]);
  }

  public function results(string $ckc_rocnik) {
    $ckc_rocnik = empty($ckc_rocnik) ? $this->default_year() :$ckc_rocnik;
    return $this->get_results_table_render_array($ckc_rocnik);
  }

  public function results_title($ckc_rocnik) {
    $ckc_rocnik = empty($ckc_rocnik) ? $this->default_year() :$ckc_rocnik;
    return "CKČ {$ckc_rocnik} - výsledky prací v kategoriích";
  }

  private function default_year() {
    return CkcHodnoceniService::get_years()[0]['name'];
  }

  private function get_results_table_render_array(string $ckc_rocnik) {
    $render = [
      '#attached' => [
        'library' => [
          'ckc_hodnoceni/vysledky',
        ],
      ],
    ];

    $table_base = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'ID práce', 'class' => ['ckc-work-id']],
        ['data' => 'Název práce', 'class' => ['ckc-work-title']],
        ['data' => 'Počet bodů', 'class' => ['ckc-work-points']],
        ['data' => Markup::create('MLOK<br>Počet hlasů'), 'class' => ['ckc-work-mlok']],
      ],
      '#rows' => [],
      '#empty' => '',
    ];

    $categories = CkcHodnoceniService::get_categories();
    $table = $table_base;
    foreach ($categories as $category) {
      $works = CkcHodnoceniService::works($ckc_rocnik, $category['code'], true);
      $results = CkcHodnoceniDB::get_works_order($ckc_rocnik, $category['code']);
      $rows = [];
      foreach ($results as $result) {
        $rows[] = [
          $result['work_id'],
          $works[$result['work_id']],
          $result['points'],
          $result['mlok'],
        ];
      }
      $table['#empty'] = "Žádné výsledky pro kategorii {$category['name']}...";
      $table['#rows'] = $rows;
      $render[$category['name_clean'].' title'] = ['#markup' => Markup::create("<h2>Výsledky v kategorii: <em>{$category['name']}</em></h2>")];
      $render[$category['name_clean']] = $table;
    }

    return $render;
  }

}
