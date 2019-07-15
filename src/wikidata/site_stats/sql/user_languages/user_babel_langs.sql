SELECT
  babel_lang,
  count(*) AS count
FROM babel
WHERE babel_user IN
(SELECT DISTINCT actor_user
  FROM recentchanges
  JOIN wikidatawiki.actor ON rc_actor = actor_id
    WHERE actor_user != 0
		  AND rc_bot = 0
		  AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
		  AND UNIX_TIMESTAMP(rc_timestamp) >= UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day)))
GROUP BY babel_lang
ORDER BY count DESC;
