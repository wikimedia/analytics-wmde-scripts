#!/bin/bash

dateISO=`date --date=today --iso-8601=date`

total_pages=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_total_pages from site_stats" wikidatawiki)

# Start building the SQL
sql='INSERT INTO wikidata_site_stats_total_pages (date,count) VALUES '
sql="$sql ('$dateISO', '$total_pages');"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
