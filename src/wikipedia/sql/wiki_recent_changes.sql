SELECT
    COUNT(*) AS total_changes,
    SUM(CASE WHEN rc_source = 'wb' THEN 1 ELSE 0 END) AS wikidata_changes
FROM (
    SELECT rc_source
    FROM {{WIKI}}.recentchanges
) AS items;