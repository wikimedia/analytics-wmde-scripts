SELECT pl_namespace AS namespace, COUNT(*) AS count
FROM wikidatawiki.`pagelinks`
WHERE pl_namespace IN (?, ?, ?, ?)
GROUP BY pl_namespace;
