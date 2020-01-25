<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcRatingController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;

class CkcRatingController extends ControllerBase {

  const WEBFORM_ID = 'hodnotici_formular_ckc';

  public function rate(string $ckc_rocnik, string $ckc_kategorie) {
    if ($sid = $this->get_submission_id($ckc_rocnik, $ckc_kategorie)) {
      return $this->redirect('ckc_hodnoceni.rating.edit', ['ckc_rocnik' => $ckc_rocnik, 'ckc_kategorie' => $ckc_kategorie]);
    }
    return [
      '#type' => 'webform',
      '#webform' => self::WEBFORM_ID,
      '#default_data' => [
        'ckc_rocnik' => $ckc_rocnik,
        'ckc_kategorie' => $ckc_kategorie,
      ],
    ];
  }

  public function rate_title($ckc_rocnik, $ckc_kategorie) {
    return "CKČ {$ckc_rocnik} - hodnotit práce v kategorii {$ckc_kategorie}";
  }

  public function rate_edit(string $ckc_rocnik, string $ckc_kategorie) {
    if ($sid = $this->get_submission_id($ckc_rocnik, $ckc_kategorie)) {
      return [
        '#type' => 'webform',
        '#webform' => self::WEBFORM_ID,
        '#sid' => $sid,
      ];
    }
    return $this->redirect('ckc_hodnoceni.rating', ['ckc_rocnik' => $ckc_rocnik, 'ckc_kategorie' => $ckc_kategorie]);
  }

  public function rate_edit_title($ckc_rocnik, $ckc_kategorie) {
    return "CKČ {$ckc_rocnik} - upravit hodnocení pro kategorii {$ckc_kategorie}";
  }

  private function get_submission_id(string $ckc_rocnik, string $ckc_kategorie) {
    $query = \Drupal::service('webform_query');
    $query->setWebform(self::WEBFORM_ID)
      ->addCondition('ckc_rocnik', $ckc_rocnik)
      ->addCondition('ckc_kategorie', $ckc_kategorie);
    $results = $query->execute();
    return !empty($results[0]) && $results[0]->sid ? $results[0]->sid : FALSE;
  }

}
