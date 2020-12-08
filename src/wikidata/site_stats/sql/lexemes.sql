SELECT pp_propname, SUM(pp_sortkey) as count
FROM wikidatawiki.page_props
WHERE pp_propname IN ('wbl-senses', 'wbl-forms') GROUP BY pp_propname
