#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron at 12 hours every day.
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    date +"%F %T daily.12.sh No argument supplied!"
    exit 1
fi
date +"%F %T daily.12.sh Started!"

# Logrotate is at 6:25, + time for rsync (hourly?), 12 gives us roughly 6 hours
eval "$1/src/wikidata/apiLogScanner.php"

date +"%F %T daily.12.sh Ended!"