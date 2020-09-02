<?php

namespace Drupal\ckc_hodnoceni;

/**
 * Base CKC data.
 */
class CkcHodnoceniBase {

  public const CKC_HODNOCENI_PLACES = [
    'order_1' => '1. místo:',
    'order_2' => '2. místo:',
    'order_3' => '3. místo:',
    'order_4' => '4. místo:',
    'order_5' => '5. místo:',
    'order_6' => '6. místo:',
  ];

  public const CKC_HODNOCENI_TABLE_FIELDS_BASE = [
    'rid', 'ckc_year', 'ckc_category', 'uid', 'exclude_first_place', 'note', 'status',
  ];

  public const CKC_HODNOCENI_TABLE_FIELDS_PLACES = [
    'order_1_1',
    'order_2_1', 'order_2_2',
    'order_3_1', 'order_3_2', 'order_3_3',
    'order_4_1', 'order_4_2', 'order_4_3', 'order_4_4',
    'order_5_1', 'order_5_2', 'order_5_3', 'order_5_4', 'order_5_5',
    'order_6_1', 'order_6_2', 'order_6_3', 'order_6_4', 'order_6_5', 'order_6_6',
  ];

}

