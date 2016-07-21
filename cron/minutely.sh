#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron every minute.
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    echo "No argument supplied"
    exit 1
fi

eval "$1/src/wikidata/dispatch.php"
eval "$1/src/wikidata/recentChanges.php"
eval "$1/src/wikidata/sparql/minutely.php"
