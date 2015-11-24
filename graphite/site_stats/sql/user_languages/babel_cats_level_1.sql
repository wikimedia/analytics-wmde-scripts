-- Get all level 1 babel categories
CREATE TEMPORARY TABLE staging.babel_cats_level_1 AS (
	SELECT page_title
	FROM wikidatawiki.categorylinks
	LEFT JOIN page on page.page_id = categorylinks.cl_from
	WHERE cl_to = "Babel_-_Users_by_language"
	AND cl_type = 'subcat'
);