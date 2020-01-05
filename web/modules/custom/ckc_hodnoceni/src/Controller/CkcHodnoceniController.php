<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcHlasovaniController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

class CkcHodnoceniController {

  const WEBFORM_ID = 'hodnotici_formular_ckc';

  public function hlasovani(string $ckc_rocnik, string $ckc_kategorie) {
//    ksm(WebformSubmission::load(26)->getData());

    if ($sid = $this->is_submitted($ckc_rocnik, $ckc_kategorie)) {
      return [
        '#type' => 'webform',
        '#webform' => self::WEBFORM_ID,
        '#sid' => $sid,
      ];
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

  public function hlasovani_title($ckc_rocnik, $ckc_kategorie) {
    return "CKČ {$ckc_rocnik} - hlasování v kategorii {$ckc_kategorie}";
  }

  private function is_submitted(string $ckc_rocnik, string $ckc_kategorie) {
    $query = \Drupal::service('webform_query');
    $query->setWebform(self::WEBFORM_ID)
      ->addCondition('ckc_rocnik', $ckc_rocnik)
      ->addCondition('ckc_kategorie', $ckc_kategorie);
    $results = $query->execute();
    dvm($results);


//    $query = \Drupal::entityTypeManager()
//      ->getStorage('webform_submission')
//      ->getQuery();
//    $query->condition('webform_id', self::WEBFORM_ID);
//    $query->condition('uid', \Drupal::currentUser()->id());
//    $query->condition('data:ckc_rocnik', $ckc_rocnik);
//    $result = $query->execute();

    if (!empty($result)) {
      ksm($result);
    }

    return FALSE;
  }
}
