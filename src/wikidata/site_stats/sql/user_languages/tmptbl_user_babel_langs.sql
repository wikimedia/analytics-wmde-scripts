-- Get all user pages that have these categories
-- Strip the category name down so we just have a language left
CREATE TEMPORARY TABLE staging.user_babel_langs AS (
	SELECT
		page_title as user,
		TRIM( TRAILING "-N" FROM TRIM( TRAILING "-5" FROM TRIM( TRAILING "-4" FROM TRIM( TRAILING "-3" FROM TRIM( TRAILING "-2" FROM TRIM( TRAILING "-1" FROM TRIM( TRAILING "-0" FROM TRIM( LEADING "User_" FROM  cl_to ))))))))
			AS language
	FROM wikidatawiki.categorylinks
	LEFT JOIN page ON page.page_id = categorylinks.cl_from
	WHERE cl_to IN(
		SELECT DISTINCT page_title FROM (
			SELECT * FROM staging.babel_cats_level_1
			UNION ALL
			SELECT * FROM staging.babel_cats_level_2
		) AS a
	)
	AND page_namespace = 2
	AND cl_type = 'page'
);