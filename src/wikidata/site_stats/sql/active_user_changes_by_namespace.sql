SELECT
		actor_name,
		rc_namespace as namespace,
		COUNT( * ) as changes
	FROM wikidatawiki.recentchanges
	JOIN wikidatawiki.actor ON rc_actor = actor_id
	WHERE actor_user != 0
	AND rc_bot = 0
	AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
	AND rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 day), '%Y%m%d%H%i%s')
	GROUP BY actor_name, rc_namespace;
