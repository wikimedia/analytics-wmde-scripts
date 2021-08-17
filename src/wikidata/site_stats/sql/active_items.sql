SELECT COUNT(*) AS count
FROM (
	SELECT rc_title
	FROM wikidatawiki.recentchanges
	WHERE rc_namespace = 0
	AND rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 day), '%Y%m%d%H%i%s')
	GROUP BY rc_title
	HAVING COUNT(*) >= 1
) AS items;
