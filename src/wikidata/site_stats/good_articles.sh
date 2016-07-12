#!/bin/bash
#
# @author Addshore
# Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
#
# See See https://www.mediawiki.org/wiki/Manual:Site_stats_table

sql="select ss_good_articles from site_stats"
value=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "$sql" wikidatawiki)
echo "daily.wikidata.site_stats.good_articles $value `date +%s`" | nc -q0 graphite.eqiad.wmnet 2003
