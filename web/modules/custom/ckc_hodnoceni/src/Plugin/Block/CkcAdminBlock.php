<?php

namespace Drupal\ckc_hodnoceni\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'CkcAdminBlock' Block.
 *
 * @Block(
 *   id = "ckc_admin_block",
 *   admin_label = @Translation("CKČ Administration"),
 *   category = @Translation("CKČ"),
 * )
 */
class CkcAdminBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $year = 2020;
    return [
      'links' => [
        'import' => [
          '#type' => 'link',
          '#title' => 'Import povídek',
          '#url' => Url::fromRoute('ckc_hodnoceni.admin.works.import', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Hromadný import povídek do kategorií',
          ],
        ],
        'import-votes' => [
          '#type' => 'link',
          '#title' => 'Import hlasů',
          '#url' => Url::fromRoute('ckc_hodnoceni.admin.votes.import', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Hromadný import hlasů',
          ],
        ],
        'status' => [
          '#type' => 'link',
          '#title' => 'Stav hlasování',
          '#url' => Url::fromRoute('ckc_hodnoceni.admin.status', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Stav hlasování',
          ],
        ],
        'results' => [
          '#type' => 'link',
          '#title' => 'Výsledky',
          'url' => Url::fromRoute('ckc_hodnoceni.admin.results', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Výsledky',
          ],
        ],
        'results-final' => [
          '#type' => 'link',
          '#title' => 'Výsledky kompletní',
          '#url' => Url::fromRoute('ckc_hodnoceni.admin.results_final', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Výsledky kompletní',
          ],
        ],
        'results-export' => [
          '#type' => 'link',
          '#title' => 'Výsledky - export',
          '#url' => Url::fromRoute('ckc_hodnoceni.admin.results.export', ['ckc_rocnik' => $year]),
          '#attributes' => [
            'title' => 'Hromadný export výsledků',
          ],
        ],
      ],
    ];
  }

}
