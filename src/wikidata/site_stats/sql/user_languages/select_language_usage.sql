-- SELECT Count overall language usage
-- 1 user can use multiple languages (babel)
-- If no babel is set user interface language is used
-- Only count users that have been active in the last 30 days
SELECT count(*) AS count, language FROM (
	SELECT * FROM staging.user_interface_langs_no_babel
	UNION ALL
	SELECT * FROM staging.user_babel_langs
) AS omg
INNER JOIN staging.active_user_changes on omg.user = staging.active_user_changes.user
WHERE staging.active_user_changes.changes >= 1
GROUP BY language;