SELECT pp_propname, SUM(pp_value) as count
FROM wikidatawiki.page_props
WHERE pp_propname IN ('wbl-senses', 'wbl-forms') GROUP BY pp_propname
