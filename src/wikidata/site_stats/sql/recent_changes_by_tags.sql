SELECT ctd_name, COUNT(*) AS count
FROM wikidatawiki.recentchanges
JOIN wikidatawiki.change_tag ON ct_rc_id = rc_id
JOIN wikidatawiki.change_tag_def ON ctd_id = ct_tag_id
WHERE rc_timestamp >= ?
AND rc_timestamp < ?
AND rc_source = 'mw.edit'
AND ctd_name IN ('data-bridge', 'client-linkitem-change', 'client-automatic-update', 'wikidata-ui', 'termbox')
GROUP BY ctd_name
