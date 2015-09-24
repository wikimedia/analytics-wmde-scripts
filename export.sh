#!/bin/bash
#
#Simple script to create tsv files from the tables used

mysqldefaults="/etc/mysql/conf.d/analytics-research-client.cnf"
mysqlhost="analytics-store.eqiad.wmnet"

mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_social" > wikidata_social.tsv
mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_site_stats" > wikidata_site_stats.tsv
mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_getclaims_property_use" > wikidata_getclaims_property_use.tsv