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
    $years = CkcHodnoceniService::year_map();
    return [
      '#markup' => '<h2>Seznam zapsaných ročníkú</h2>',
    ];
  }

  public function main_title() {
    return "CKČ administrace";
  }

  public function status(string $ckc_rocnik) {
    $years = CkcHodnoceniService::year_map();
    \Drupal::service('user.private_tempstore')->get('ckc_hodnoceni')->set('year_selected', $years[$ckc_rocnik]['id']);
    return [
      'data' => [
        '#type' => 'view',
        '#name' => 'ckc_hlasovani',
        '#display_id' => 'default',
        '#arguments' => [
          $ckc_rocnik,
        ],
      ],
    ];
  }

  public function status_title(string $ckc_rocnik) {
    return "CKČ {$ckc_rocnik} - stav hlasování";
  }

  public function status_switch(string $ckc_rocnik, int $rid) {
    $active = CkcHodnoceniService::active($ckc_rocnik);
    if ($active) {
      CkcHodnoceniDB::rate_status_switch($rid);
      $view = Views::getView('ckc_hlasovani');
      $view->storage->invalidateCaches();
    } else {
      $this->messenger()->addWarning("Změna pro zahrnutí práce do hodnocení se neuskutečnila, protože ročník {$ckc_rocnik} je uzamčen!");
    }
    return $this->redirect('ckc_hodnoceni.admin.status', ['ckc_rocnik' => $ckc_rocnik]);
  }

  public function results(string $ckc_rocnik) {
    return $this->get_results_table_render_array($ckc_rocnik);
  }

  public function results_title($ckc_rocnik) {
    return "CKČ {$ckc_rocnik} - výsledky prací v kategoriích";
  }

  public function results_final(string $ckc_rocnik) {
    return $this->get_results_final_table_render_array($ckc_rocnik);
  }

  public function results_final_title($ckc_rocnik) {
    $year = CkcHodnoceniService::year_map()[$ckc_rocnik]['year'];
    return "Kompletní výsledky {$year}. ročníku CKČ";
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

  private function get_results_final_table_render_array(string $ckc_rocnik) {
    $render = [
      '#attached' => [
        'library' => [
          'ckc_hodnoceni/vysledky',
        ],
      ],
      'description' => [
        '#markup' => Markup::create(<<<'EOD'
          <p>Pro výpočty bodového hodnocení je používán tento vzorec: Součty = suma získaných bodů
          - viz počet získaných n-tých míst, n-té místo je ohodnoceno 60/n body (60, 30, 20, 15, 12,
          10). Je-li na začátku řádku =, došlo u povídek ke shodě bodů a jsou zařazeny na stejné
          pořadí.</p>
        EOD),
      ],
    ];

    $table_base = [
      '#type' => 'table',
      '#header' => [
        ['data' => 'Pořadí', 'class' => ['ckc-work-id']],
        ['data' => 'Součty', 'class' => ['ckc-work-points']],
        ['data' => '1', 'class' => ['ckc-work-votes']],
        ['data' => '2', 'class' => ['ckc-work-votes']],
        ['data' => '3', 'class' => ['ckc-work-votes']],
        ['data' => '4', 'class' => ['ckc-work-votes']],
        ['data' => '5', 'class' => ['ckc-work-votes']],
        ['data' => '6', 'class' => ['ckc-work-votes']],
        ['data' => 'ID', 'class' => ['ckc-work-votes']],
        ['data' => 'Název', 'class' => ['ckc-work-title']],
      ],
      '#rows' => [],
      '#empty' => '',
    ];

    $categories = CkcHodnoceniService::get_categories();
    $table = $table_base;
    foreach ($categories as $category) {
      $previous_points = 0;
      $place = 1;
      $works = CkcHodnoceniService::works($ckc_rocnik, $category['code'], true);
      $results = CkcHodnoceniDB::get_works_order($ckc_rocnik, $category['code']);
      $rows = [];
      foreach ($results as $result) {
        $rows[] = [
          $previous_points === $result['points'] ? '=' : "{$place}.",
          $result['points'],
          $result['place_1'] == 0 ? '-' : $result['place_1'],
          $result['place_2'] == 0 ? '-' : $result['place_2'],
          $result['place_3'] == 0 ? '-' : $result['place_3'],
          $result['place_4'] == 0 ? '-' : $result['place_4'],
          $result['place_5'] == 0 ? '-' : $result['place_5'],
          $result['place_6'] == 0 ? '-' : $result['place_6'],
          $result['work_id'],
          $works[$result['work_id']],
        ];
        $previous_points = $result['points'];
        $place++;
      }
      $table['#empty'] = "Žádné výsledky pro kategorii {$category['name']}...";
      $table['#rows'] = $rows;
      $render[$category['name_clean'].' title'] = ['#markup' => Markup::create("<h2>Kompletní výsledky v kategorii: <em>{$category['name']}</em></h2>")];
      $render[$category['name_clean']] = $table;
    }

    return $render;
  }

}
