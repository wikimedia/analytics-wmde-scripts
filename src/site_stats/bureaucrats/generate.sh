#!/bin/bash

dateISO=`date --date=today --iso-8601=date`

metric=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select count(*) as count from user_groups where ug_group = 'bureaucrats' group by ug_group" wikidatawiki)

# Start building the SQL
sql='INSERT INTO wikidata_site_stats_bureaucrats (date,count) VALUES '
sql="$sql ('$dateISO', '$metric');"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
