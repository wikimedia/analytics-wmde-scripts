SELECT
	a.sitelinks AS sitelinks,
	COUNT(*) AS count
FROM (
	SELECT
		ips_item_id AS item,
		COUNT(*) AS sitelinks
	FROM wikidatawiki.wb_items_per_site
	GROUP BY ips_item_id
) AS a
GROUP BY sitelinks