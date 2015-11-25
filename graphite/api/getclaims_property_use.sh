#!/bin/bash
#
# @author Addshore
#
# This shows the number of calls to the wbgetclaims api module broken down by property for a 24 hour period.
# This metric is taken from the api.log file archives generated on fluorine that are rsynced to stat1002.
# The 24 hour period is from 00:00 to 23:59 UTC.

# Run for date given, or yesterday
if [ $# -eq 0 ]; then
    dateISO=`date --date=yesterday --iso-8601=date`
else
    dateISO=$1
fi
nextDateISO=`date --date="$dateISO + 1 day" --iso-8601=date`

# Get a date stamp to be used later
dateStamp=`echo $dateISO | tr -d '-'`
nextDateStamp=`echo $nextDateISO | tr -d '-'`

# Get the location of the last api log
apilog="/a/mw-log/archive/api/api.log-$dateStamp.gz"
nextapilog="/a/mw-log/archive/api/api.log-$nextDateStamp.gz"

# Make sure the files we want exist
if [ ! -f $apilog ]; then
    echo "File not found: (apilog) $apilog"
        exit;
fi
if [ ! -f $nextApilog ]; then
    echo "File not found: (nextapilog) $nextapilog"
        exit;
fi

# Run the main command
output=`zgrep $dateISO $apilog $nextapilog | grep action=wbgetclaims | grep wikidatawiki | egrep -o 'property=P[0-9]+' | sort | uniq -c | sort -nr`

# Iterate over each line and add to graphite
while read -r line; do
                property=`cut -d "=" -f 2 <<< "$line"`
                value=`cut -d " " -f 1 <<< "$line"`
                echo "daily.wikidata.api.wbgetclaims.properties.$property $value `date -d \"$dateStamp\" +%s`" | nc -q0 graphite.eqiad.wmnet 2003
done <<< "$output"
