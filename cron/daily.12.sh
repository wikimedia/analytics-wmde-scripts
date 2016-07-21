#!/bin/bash
# @author Addshore
#
# This script should be run through cron at 12 hours every day.
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    echo "No argument supplied"
    exit 1
fi

# Logrotate is at 6:25, + time for rsync (hourly?), 12 gives us roughly 6 hours
eval "$1/src/wikidata/apiLogScanner.php"