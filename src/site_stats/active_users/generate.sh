#!/bin/bash

dateISO=`date --date=today --iso-8601=date`

active_users=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_active_users from site_stats" wikidatawiki)

# Start building the SQL
sql='INSERT INTO wikidata_site_stats_active_users (date,count) VALUES '
sql="$sql ('$dateISO', '$active_users');"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
