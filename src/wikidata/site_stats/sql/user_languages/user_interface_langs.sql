SELECT
		COALESCE(user_properties.up_value,"en") as language,
		count(*) as count
	FROM user
	LEFT JOIN ( SELECT * FROM user_properties WHERE user_properties.up_property = 'language' ) AS user_properties
	ON user.user_id = user_properties.up_user
	WHERE user_id IN (SELECT DISTINCT actor_user
FROM recentchanges
JOIN wikidatawiki.actor ON rc_actor = actor_id
WHERE actor_user != 0
		AND rc_bot = 0
		AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
		AND rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 day), '%Y%m%d%H%i%s'))
  GROUP BY language
  ORDER BY count(*);
