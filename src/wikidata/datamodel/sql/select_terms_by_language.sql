SELECT
	COUNT(*) AS count,
	term_entity_type,
	term_type,
	term_language
FROM wikidatawiki.wb_terms
GROUP BY term_entity_type, term_type, term_language;