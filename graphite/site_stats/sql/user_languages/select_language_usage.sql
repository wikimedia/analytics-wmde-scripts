-- SELECT Count overall language usage
-- 1 user can use multiple languages (babel)
-- If no babel is set user interface language is used
SELECT count(*) AS count, language FROM (
	SELECT * FROM staging.user_interface_langs_no_babel
	UNION ALL
	SELECT * FROM staging.user_babel_langs
) AS omg
GROUP BY language
ORDER BY count ASC;