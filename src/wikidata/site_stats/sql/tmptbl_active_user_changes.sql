CREATE TEMPORARY TABLE staging.active_user_changes AS (
	SELECT
		rc_user_text AS user,
		COUNT( * ) as changes
	FROM wikidatawiki.recentchanges
	WHERE rc_user != 0
	AND rc_bot = 0
	AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
	AND UNIX_TIMESTAMP(rc_timestamp) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day))
	GROUP BY rc_user_text
);