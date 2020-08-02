<?php

use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Serializer;

$data_csv = <<<EOD
rid,ckc_year,ckc_category,uid,order_1_1,order_1_1_exclude,order_2_1,order_2_2,order_3_1,order_3_2,order_3_3,order_4_1,order_4_2,order_4_3,order_4_4,order_5_1,order_5_2,order_5_3,order_5_4,order_5_5,order_6_1,order_6_2,order_6_3,order_6_4,order_6_5,order_6_6,note
2,2020,1,17,130,0,134,111,112,"","",128,114,127,133,118,120,"","","",110,119,121,129,132,"",""
3,2020,0,39,012,0,003,017,004,009,001,008,014,006,002,007,015,010,016,"",011,013,018,019,005,"",""
4,2020,1,39,124,0,119,106,104,127,120,103,114,122,129,121,101,112,107,117,111,118,109,110,113,105,""
5,2020,2,39,215,0,208,209,212,227,201,221,228,218,211,231,223,207,204,219,233,206,203,225,216,214,""
6,2020,3,39,311,0,309,307,303,304,310,301,"","","",306,308,"","","",302,305,312,"","","",""
7,2020,0,40,012,0,001,014,009,003,"","","","","","","","","","","","","","","","",""
8,2020,1,40,117,0,110,131,105,133,106,130,109,127,112,"","","","","","","","","","","",Mlok v prvním pořadí
9,2020,0,41,001,0,012,004,014,008,017,003,006,007,010,005,015,"","","","","","","","","",""
10,2020,1,41,112,0,106,122,124,133,134,127,129,130,131,128,105,120,119,125,109,103,110,113,121,123,""
11,2020,2,41,206,0,205,207,212,211,204,208,231,209,215,232,219,218,216,229,227,201,228,233,210,203,""
12,2020,3,42,304,0,303,306,302,307,"",310,311,312,"","","","","","",301,308,309,"","","",Mloka neudělovat
EOD;

$cat_0 = array_map(function($v) { return '0'.str_pad($v, 2, '0', STR_PAD_LEFT); }, range(1,19)); # mikropovidka
$cat_1 = array_map(function($v) { return '1'.str_pad($v, 2, '0', STR_PAD_LEFT); }, range(1,34)); # kratka povidka
$cat_2 = array_map(function($v) { return '2'.str_pad($v, 2, '0', STR_PAD_LEFT); }, range(1,33)); # povidka
$cat_3 = array_map(function($v) { return '3'.str_pad($v, 2, '0', STR_PAD_LEFT); }, range(1,12)); # novela

$context = [
  CsvEncoder::DELIMITER_KEY => "\t",
];
$serializer = new Serializer([], [new CsvEncoder()]);
$data = $serializer->decode($data_csv, 'csv', [CsvEncoder::DELIMITER_KEY => ',']);

$query_base = 'INSERT INTO `ckc_hodnoceni_works` (`rid`, `work_id`, `work_place`, `work_place_order`, `work_mlok`) VALUES';

foreach ($data as $row) {
    $rid = $row['rid'];
    $uid = $row['uid'];
    $cat = "cat_{$row['ckc_category']}";

    $works = [];
    foreach ($row as $key => $val) {
      if (empty($val) || $key === 'order_1_1_exclude' || substr($key, 0, 6) !== 'order_' || !in_array($val, $$cat, TRUE)) {
        continue;
      }
      $works["key_{$val}"] = [
        'work_place' => (int) substr($key, 6, 1),
        'work_place_order' => (int) substr($key, 8, 1),
      ];
    }

    $inserts = [];
    foreach ($$cat as $work_id) {
      $work_place = empty($works["key_{$work_id}"]) ? 'NULL' : "'{$works["key_{$work_id}"]['work_place']}'";
      $work_place_order = empty($works["key_{$work_id}"]) ? 'NULL' : "'{$works["key_{$work_id}"]['work_place_order']}'";;
      $inserts[] = "({$rid}, '{$work_id}', {$work_place}, {$work_place_order}, 0)";
    }
    // echo "--- rid: {$rid}, user: {$uid}".PHP_EOL;
    echo $query_base.PHP_EOL;
    echo join(",\n", $inserts);
    echo ';';
    echo PHP_EOL;
}
