#!/bin/bash
#
# A table needs to exist for this script to function (see the file in /sql/create)

dateISO=`date --date=today --iso-8601=date`

#TODO A single call?
total_views=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_total_views from site_stats" wikidatawiki)
total_edits=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_total_edits from site_stats" wikidatawiki)
good_articles=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_good_articles from site_stats" wikidatawiki)
total_pages=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_total_pages from site_stats" wikidatawiki)
users=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_users from site_stats" wikidatawiki)
active_users=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_active_users from site_stats" wikidatawiki)

# Start building the SQL
sql='INSERT INTO wikidata_site_stats (date,total_views,total_edits,good_articles,total_pages,users,active_users) VALUES '
sql="$sql ('$dateISO', '$total_views', '$total_edits', '$good_articles', '$total_pages', '$users', '$active_users');"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
