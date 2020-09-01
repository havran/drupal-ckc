<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcWorksController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;

class CkcAdminWorksController extends ControllerBase {

  public function import_title(string $ckc_rocnik) {
    return [
      '#markup' => "<p>CKČ {$ckc_rocnik} - hromadný import povídek</p>",
    ];
  }

}
