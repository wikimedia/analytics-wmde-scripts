SELECT
	ips_site_id AS site,
	COUNT(*) AS count
FROM wb_items_per_site
GROUP BY ips_site_id