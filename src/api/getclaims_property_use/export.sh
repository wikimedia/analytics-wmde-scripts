#!/bin/bash

mysqldefaults="/etc/mysql/conf.d/analytics-research-client.cnf"
mysqlhost="analytics-store.eqiad.wmnet"

mysql --defaults-file=$mysqldefaults -h $mysqlhost -e "SELECT * FROM staging.wikidata_getclaims_property_use" > data.tsv
cp data.tsv /a/aggregate-datasets/wikidata/getclaims_property_use.tsv
