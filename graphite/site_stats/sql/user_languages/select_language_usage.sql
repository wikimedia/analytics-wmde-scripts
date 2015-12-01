-- SELECT Count overall language usage
-- 1 user can use multiple languages (babel)
-- If no babel is set user interface language is used
-- Only count users that have been active in the last 30 days
SELECT count(*) AS count, language FROM (
	SELECT * FROM staging.user_interface_langs_no_babel
	UNION ALL
	SELECT * FROM staging.user_babel_langs
) AS omg
LEFT JOIN user on omg.user = user.user_name
WHERE UNIX_TIMESTAMP(user.user_touched) > UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 30 day))
GROUP BY language
ORDER BY count ASC;