Pořadí prací podle počtu bodů ve vybrané kategorii, spolu s počtem hlasů pro udělení ceny MLOK.

Vstupní hodnoty:
- rok
- kategorie
```sql
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
    FROM ckc_hodnoceni_works AS w2
    LEFT JOIN ckc_hodnoceni AS h2 ON h2.rid = w2.rid
    WHERE
      1 = 1
      AND h.ckc_year = {rok}
      AND h.ckc_category = {kategorie}
      AND h.status = 1
      AND w2.work_id = w.work_id
  ) AS mlok
FROM ckc_hodnoceni_works AS w
LEFT JOIN ckc_hodnoceni AS h ON h.rid = w.rid
WHERE
  1 = 1
  AND h.ckc_year = {rok}
  AND h.ckc_category = {kategorie}
  AND h.status = 1
GROUP BY work_id
ORDER BY points DESC
```


```sql
SELECT
  h1.uid AS uid,
  (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '0') AS cat_0,
  (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '1') AS cat_1,
  (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '2') AS cat_2,
  (SELECT 1 FROM ckc_hodnoceni AS h2 WHERE h2.uid = h1.uid AND h2.ckc_category = '3') AS cat_3
FROM ckc_hodnoceni AS h1
WHERE
  1 = 1
  AND h1.ckc_year = '2020'
  AND h1.status = 1
GROUP BY h1.uid
```
