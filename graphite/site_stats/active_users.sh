#!/bin/bash
#
# @author Addshore
#
# See https://www.mediawiki.org/wiki/Manual:Site_stats_table

sql="select ss_active_users from site_stats"
value=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "$sql" wikidatawiki)
echo "daily.wikidata.site_stats.active_users $value `date +%s`" | nc -q0 graphite.eqiad.wmnet 2003
