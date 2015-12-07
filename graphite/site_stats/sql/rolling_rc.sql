SELECT COUNT(*) as count
FROM wikidatawiki.recentchanges
WHERE UNIX_TIMESTAMP(rc_timestamp) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day))