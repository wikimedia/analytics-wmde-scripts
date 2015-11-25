SELECT
  page_namespace AS namespace,
  page_is_redirect AS redirect,
  COUNT(*) AS count
FROM wikidatawiki.page
WHERE page_namespace = 0
OR page_namespace = 120
OR page_namespace = 1
GROUP BY page_namespace, page_is_redirect