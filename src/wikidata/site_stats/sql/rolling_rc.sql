SELECT COUNT(*) as count
FROM wikidatawiki.recentchanges
WHERE rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 day), '%Y%m%d%H%i%s')
