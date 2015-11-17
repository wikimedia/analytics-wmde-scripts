#!/bin/bash
#
# @author Addshore
#
# See https://www.mediawiki.org/wiki/Manual:Site_stats_table

value=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select ss_total_edits from site_stats" wikidatawiki)

echo "daily.wikidata.site_stats.total_edits $value `date +%s`" | nc -q0 graphite.eqiad.wmnet 2003
