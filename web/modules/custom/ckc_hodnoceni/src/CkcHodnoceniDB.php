<?php

namespace Drupal\ckc_hodnoceni;

use Drupal\Core\Database\Database;

class CkcHodnoceniDB {

  const CKC_HODNOCENI_QUERY_USERS_STATS = /** @lang MySQL */
    <<<'EOD'
      SELECT
        h1.uid AS uid,
        (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '0') AS cat_0,
        (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '1') AS cat_1,
        (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '2') AS cat_2,
        (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '3') AS cat_3
      FROM ckc_hodnoceni AS h1
      WHERE
        1 = 1
        AND h1.ckc_year = :ckc_year
        AND h1.status = 1
      GROUP BY h1.uid
    EOD;

  const CKC_HODNOCENI_QUERY_WORKS_ORDER = /** @lang MySQL */
    <<<'EOD'
      SELECT
        h.ckc_year,
        h.ckc_category,
        w.work_id,
        SUM(
            CASE
              WHEN w.work_place = 1 THEN 60
              WHEN w.work_place = 2 THEN 30
              WHEN w.work_place = 3 THEN 20
              WHEN w.work_place = 4 THEN 15
              WHEN w.work_place = 5 THEN 12
              WHEN w.work_place = 6 THEN 10
              ELSE 0
            END
        ) AS points,
        (
          SELECT
          SUM(w2.work_mlok)
          FROM {ckc_hodnoceni_works} AS w2
                  LEFT JOIN {ckc_hodnoceni} AS h2 ON h2.rid = w2.rid
                  WHERE
                    1 = 1
                    AND h.ckc_year = :ckc_year
          AND h.ckc_category = :ckc_category
          AND h.status = 1
          AND w2.work_id = w.work_id
        ) AS mlok
      FROM {ckc_hodnoceni_works} AS w
      LEFT JOIN {ckc_hodnoceni} AS h ON h.rid = w.rid
      WHERE
        1 = 1
        AND h.ckc_year = :ckc_year
        AND h.ckc_category = :ckc_category
        AND h.status = 1
      GROUP BY h.ckc_year, h.ckc_category, h.status, w.work_id
      ORDER BY points DESC;
    EOD;

  public static function get_users_stats(string $ckc_year) {
    $connection = Database::getConnection();
    $query = $connection->query(
      self::CKC_HODNOCENI_QUERY_USERS_STATS,
      [
        ':ckc_year' => $ckc_year,
      ],
    );
    return $query->fetchAllAssoc('uid', \PDO::FETCH_ASSOC);
  }

  public static function get_works_order(string $ckc_year, string $ckc_category) {
    $connection = Database::getConnection();
    $query = $connection->query(
      self::CKC_HODNOCENI_QUERY_WORKS_ORDER,
      [
        ':ckc_year' => $ckc_year,
        ':ckc_category' => $ckc_category,
      ],
    );
    return $query->fetchAllAssoc('work_id', \PDO::FETCH_ASSOC);
  }

}
