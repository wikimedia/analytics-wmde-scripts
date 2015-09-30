#!/bin/bash

mysqldefaults="/etc/mysql/conf.d/analytics-research-client.cnf"
mysqlhost="analytics-store.eqiad.wmnet"

mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_site_stats_good_articles" > data.tsv
cp data.tsv /a/aggregate-datasets/wikidata/site_stats_good_articles.tsv