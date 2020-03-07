<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcRatingController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;

class CkcRatingController extends ControllerBase {

  const WEBFORM_ID = 'hodnotici_formular_ckc';

  public function list(string $ckc_rocnik) {
    return [
      '#markup' => '<p>Stav list</p>',
    ];
  }

  public function list_title(string $ckc_rocnik) {
    return "CKČ {$ckc_rocnik} - stav vašeho hodnocení";
  }

//  public function rate(string $ckc_rocnik, string $ckc_kategorie) {
////    if ($sid = $this->get_submission_id($ckc_rocnik, $ckc_kategorie)) {
////      return [
////        '#type' => 'webform',
////        '#webform' => self::WEBFORM_ID,
////        '#sid' => $sid,
////      ];
////    }
////    return [
////      '#type' => 'webform',
////      '#webform' => self::WEBFORM_ID,
////      '#default_data' => [
////        'ckc_rocnik' => $ckc_rocnik,
////        'ckc_kategorie' => $ckc_kategorie,
////      ],
////    ];
//    $form = \Drupal::formBuilder()->retrieveForm('ckc_rate_form', new FormState());
//    return [
//      '#type' => 'form',
//      '#form' => $form,
//    ];
//  }

  public function rate_title($ckc_rocnik, $ckc_kategorie) {
    // if ($sid = $this->get_submission_id($ckc_rocnik, $ckc_kategorie)) {
    //   return "CKČ {$ckc_rocnik} - upravit hodnocení pro kategorii {$ckc_kategorie}";
    // }
    return "CKČ {$ckc_rocnik} - hodnotit práce v kategorii {$ckc_kategorie}";
  }

  // private function get_submission_id(string $ckc_rocnik, string $ckc_kategorie) {
  //   $sid = &drupal_static(__FUNCTION__, false);
  //   if (isset($sid) && $sid !== false) {
  //     return $sid;
  //   }
  //   $uid = $this->currentUser()->id();
  //   $query = \Drupal::service('webform_query');
  //   $query->setWebform(self::WEBFORM_ID)
  //     ->addCondition('uid', $uid, '=', 'webform_submission')
  //     ->addCondition('ckc_rocnik', $ckc_rocnik)
  //     ->addCondition('ckc_kategorie', $ckc_kategorie);
  //   $results = $query->execute();
  //   $sid = !empty($results[0]) && $results[0]->sid ? $results[0]->sid : FALSE;
  //   return $sid;
  // }

}
