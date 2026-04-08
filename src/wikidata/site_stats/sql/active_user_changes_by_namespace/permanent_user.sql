-- Note: Permanent users; not temporary users or bots.
SELECT
    actor_name,
    rc_namespace as namespace,
    COUNT( * ) as changes
FROM wikidatawiki.recentchanges
JOIN wikidatawiki.actor ON rc_actor = actor_id
JOIN wikidatawiki.user ON actor_user = user_id
WHERE
    actor_user != 0
    AND NOT user_is_temp
    AND rc_bot = 0
    AND ( rc_log_type != 'newusers' OR rc_log_type IS NULL)
    AND rc_timestamp >= DATE_FORMAT(DATE_SUB(NOW(), INTERVAL 30 day), '%Y%m%d%H%i%s')
GROUP BY actor_name, rc_namespace;
