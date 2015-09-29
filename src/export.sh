#!/bin/bash
#
#Simple script to create tsv files from the tables used
#
#This script of course requires the tables to already exist!
#This script also requires /a/aggregate-datasets/wikidata to already exist

mysqldefaults="/etc/mysql/conf.d/analytics-research-client.cnf"
mysqlhost="analytics-store.eqiad.wmnet"

mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_social" > wikidata_social.tsv
mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_site_stats" > wikidata_site_stats.tsv
mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_getclaims_property_use" > wikidata_getclaims_property_use.tsv

cp wikidata_social.tsv /a/aggregate-datasets/wikidata/social.tsv
cp wikidata_site_stats.tsv /a/aggregate-datasets/wikidata/site_stats.tsv
cp wikidata_getclaims_property_use.tsv /a/aggregate-datasets/wikidata/getclaims_property_use.tsv
