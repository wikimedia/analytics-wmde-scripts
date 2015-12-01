-- Get interface language of ONLY those users with no babel set
CREATE TEMPORARY TABLE staging.user_interface_langs_no_babel AS (
	SELECT *
	FROM staging.user_interface_langs
	WHERE user NOT IN ( SELECT DISTINCT user FROM staging.user_babel_langs )
);