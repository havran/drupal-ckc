<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcWorksController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;

class CkcWorksController extends ControllerBase {

  public function list(string $ckc_rocnik, string $ckc_kategorie) {
    return [
      '#markup' => '<p>LIST</p>',
    ];
  }

  public function edit(string $ckc_rocnik, string $ckc_kategorie) {
    return [
      '#markup' => '<p>EDIT</p>',
    ];
  }

}
