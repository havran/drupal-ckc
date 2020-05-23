<?php

/**
 * @file
 * Contains \Drupal\ckc_hodnoceni\Controller\CkcAdminExportController.
 */

namespace Drupal\ckc_hodnoceni\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ckc_hodnoceni\CkcHodnoceniService;
use Drupal\ckc_hodnoceni\CkcHodnoceniUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipStream\ZipStream;

class CkcAdminExportController extends ControllerBase {

  use CkcHodnoceniUtils;

  public function export(string $ckc_rocnik) {
    $ckc_rocnik = empty($ckc_rocnik) ? $this->default_year() : $ckc_rocnik;
    return $this->zipStream($ckc_rocnik);
  }

  private function default_year() {
    return CkcHodnoceniService::get_years()[0]['name'];
  }

  private function zipStream($ckc_rocnik) {
    $filename = "ckc-{$ckc_rocnik}-export.zip";

    $response = new StreamedResponse(function() use ($filename, $ckc_rocnik) {
      $zip = new ZipStream($filename);
      foreach($this->readRateRecords($ckc_rocnik) as $rid => $record) {
        $zip->addFile("{$record['_ckc_year']}_{$record['_category_id']}_{$record['_name_clean']}.txt", $this->renderRate($record));
      }
      $zip->finish();
    });

    $response->headers->set('Content-Type', 'application/x-zip');
    $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

    return $response;
  }

  private function renderRate($data) {
    $renderable = [
      '#theme' => 'ckc_rate_text',
      '#data' => $data,
    ];
    return \Drupal::service('renderer')->renderPlain($renderable);
  }

}

