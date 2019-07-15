SELECT
    COALESCE(user_properties.up_value,"en") as language,
		count(*) as count
	FROM user
	LEFT JOIN ( SELECT * FROM user_properties WHERE user_properties.up_property = 'language' ) AS user_properties
	ON user.user_id = user_properties.up_user
	INNER JOIN babel on babel_user = user_id and babel_lang = COALESCE(user_properties.up_value,"en")
	WHERE user_id IN (SELECT DISTINCT actor_user
FROM recentchanges
JOIN wikidatawiki.actor ON rc_actor = actor_id
WHERE actor_user != 0
		AND rc_bot = 0
		AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
		AND UNIX_TIMESTAMP(rc_timestamp) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day)))
  GROUP BY language
  ORDER BY count(*);
