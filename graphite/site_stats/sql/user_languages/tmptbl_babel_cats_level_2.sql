-- Get all level 2 babel categories (so we include en-gb etc.)
CREATE TEMPORARY TABLE staging.babel_cats_level_2 AS (
	SELECT page_title
	FROM wikidatawiki.categorylinks
	LEFT JOIN page on page.page_id = categorylinks.cl_from
	WHERE cl_to IN ( SELECT page_title FROM staging.babel_cats_level_1 )
	AND cl_type = 'subcat'
);