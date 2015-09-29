#!/bin/bash

mysqldefaults="/etc/mysql/conf.d/analytics-research-client.cnf"
mysqlhost="analytics-store.eqiad.wmnet"

mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_social_facebook" > data.tsv
cp data.tsv /a/aggregate-datasets/wikidata/social_facebook.tsv
