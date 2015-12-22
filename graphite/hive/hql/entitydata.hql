SELECT
    COUNT(1) as count,
    agent_type,
    content_type
FROM wmf.webrequest
WHERE webrequest_source = 'text'
    AND year = <<YEAR>>
    AND month = <<MONTH>>
    AND day = <<DAY>>
    AND http_status = 200
    AND normalized_host.project_class = 'wikidata'
    AND uri_path rlike '^/wiki/Special:EntityData/.*$'
GROUP BY
    year, month, day,
    agent_type,
    content_type
ORDER BY
    content_type, agent_type
LIMIT 100000;