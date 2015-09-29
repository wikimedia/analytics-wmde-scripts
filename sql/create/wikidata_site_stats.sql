CREATE TABLE IF NOT EXISTS wikidata_site_stats
  (
     date          DATE NOT NULL,
     total_views   BIGINT(20) NOT NULL,
     total_edits   BIGINT(20) NOT NULL,
     good_articles BIGINT(20) NOT NULL,
     total_pages   BIGINT(20) NOT NULL,
     users         BIGINT(20) NOT NULL,
     active_users  BIGINT(20) NOT NULL
  );