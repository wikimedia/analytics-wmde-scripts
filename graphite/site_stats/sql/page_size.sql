SELECT
  page_namespace AS namespace,
  AVG( page_len ) AS avg,
  MAX( page_len ) AS max
FROM wikidatawiki.page
WHERE page_namespace=120
OR page_namespace=0
GROUP BY page_namespace