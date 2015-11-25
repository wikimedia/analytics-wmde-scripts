SELECT
  COUNT(*) AS count,
  pi_type AS type
FROM wikidatawiki.wb_property_info
GROUP BY pi_type