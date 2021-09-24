#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron every minute.
# The first parameter should be the directory this repo is checked out into.
# Scripts that are run here need to be executable (+x) in git.

if [ -z "$1" ]
  then
    date +"%F %T minutely.sh No argument supplied!"
    exit 1
fi
date +"%F %T minutely.sh Started!"

eval "$1/src/wikidata/dispatch.php" &
eval "$1/src/wikidata/wb_changes.php" &
eval "$1/src/wikidata/maxlag.php" &
eval "$1/src/wikidata/recentChanges.php" &

date +"%F %T minutely.sh Waiting!"
wait
date +"%F %T minutely.sh Ended!"
