SELECT rc_namespace AS namespace, COUNT(*) AS count
FROM wikidatawiki.recentchanges
WHERE rc_timestamp >= ?
AND rc_timestamp < ?
AND rc_namespace IN (?, ?, ?, ?, ?, ?, ?, ?)
AND rc_source = 'mw.edit'
GROUP BY rc_namespace
