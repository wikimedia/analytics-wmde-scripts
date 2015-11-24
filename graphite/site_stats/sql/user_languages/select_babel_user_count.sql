-- SELECT number of users using babel
SELECT
	COUNT(DISTINCT user) AS count
FROM staging.user_babel_langs;