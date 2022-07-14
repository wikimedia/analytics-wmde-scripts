SELECT COUNT(*) AS count
FROM wikidatawiki.recentchanges
JOIN wikidatawiki.change_tag ON ct_rc_id = rc_id
JOIN wikidatawiki.change_tag_def ON ctd_id = ct_tag_id
WHERE rc_timestamp >= ?
AND rc_timestamp < ?
AND rc_new = 1
AND rc_namespace = 146 /* Lexeme: */
AND ctd_name = 'wikidata-ui'
GROUP BY ctd_name
