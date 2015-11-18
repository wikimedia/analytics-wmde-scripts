#!/bin/bash
#
# @author Addshore
#
# The number of users in the propertycreators on a give day.
# Generated using the user_groups table.
#
# SELECT ug_group, count(*) AS count FROM user_groups GROUP BY ug_group;

value=$(mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -se "select count(*) as count from user_groups where ug_group = 'propertycreator' group by ug_group" wikidatawiki)

echo "daily.wikidata.site_stats.user_groups.propertycreators $value `date +%s`" | nc -q0 graphite.eqiad.wmnet 2003
