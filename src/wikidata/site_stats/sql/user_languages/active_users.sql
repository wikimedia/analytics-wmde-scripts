SELECT DISTINCT rc_user
FROM wikidatawiki.recentchanges
WHERE rc_user != 0
		AND rc_bot = 0
		AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
		AND UNIX_TIMESTAMP(rc_timestamp) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day));
