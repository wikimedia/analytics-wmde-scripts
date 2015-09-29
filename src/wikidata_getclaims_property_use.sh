#!/bin/bash
#
# A table needs to exist for this script to function (see the file in /sql/create)

# If I want data for the 22nd I need logs rotated on 22nd and 23rd

if [ $# -eq 0 ]; then
        echo "No argument provided, using default of yesterday!"
        dateISO=`date --date=yesterday --iso-8601=date`
else
        dateISO=$1
fi
nextDateISO=`date --date="$dateISO + 1 day" --iso-8601=date`

echo "Running for calls on $dateISO"

# Get a date stamp to be used later
dateStamp=`echo $dateISO | tr -d '-'`
nextDateStamp=`echo $nextDateISO | tr -d '-'`

# Get the location of the last api log
apilog="/a/mw-log/archive/api.log-$dateStamp.gz"
nextapilog="/a/mw-log/archive/api.log-$nextDateStamp.gz"

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
output=`zgrep action=wbgetclaims $apilog $nextapilog | grep wikidatawiki | egrep -o 'property=P[0-9]+' | sort | uniq -c | sort -nr`

# Start building the SQL
sql='INSERT INTO wikidata_getclaims_property_use (date,property,count) VALUES '

# Iterate over each line and add to the SQL
while read -r line; do
                # Each line is "11019262 property=P373"
                property=`cut -d "=" -f 2 <<< "$line"`
                count=`cut -d " " -f 1 <<< "$line"`
                sql="$sql ('$dateISO', '$property', '$count'), "
done <<< "$output"

# Finish the SQL
sql=${sql::-2}
sql="$sql;"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
addshore@stat1002:~/src$ clear

addshore@stat1002:~/src$ cat wikidata_getclaims_property_use.sh
#!/bin/bash
#
# A table needs to exist for this script to function:
# CREATE TABLE IF NOT EXISTS wikidata_getclaims_property_use ( date DATE NOT NULL, property VARCHAR(6) NOT NULL, count INT(12) );
#

# If I want data for the 22nd I need logs rotated on 22nd and 23rd

if [ $# -eq 0 ]; then
        echo "No argument provided, using default of yesterday!"
        dateISO=`date --date=yesterday --iso-8601=date`
else
        dateISO=$1
fi
nextDateISO=`date --date="$dateISO + 1 day" --iso-8601=date`

echo "Running for calls on $dateISO"

# Get a date stamp to be used later
dateStamp=`echo $dateISO | tr -d '-'`
nextDateStamp=`echo $nextDateISO | tr -d '-'`

# Get the location of the last api log
apilog="/a/mw-log/archive/api.log-$dateStamp.gz"
nextapilog="/a/mw-log/archive/api.log-$nextDateStamp.gz"

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
output=`zgrep action=wbgetclaims $apilog $nextapilog | grep wikidatawiki | egrep -o 'property=P[0-9]+' | sort | uniq -c | sort -nr`

# Start building the SQL
sql='INSERT INTO wikidata_getclaims_property_use (date,property,count) VALUES '

# Iterate over each line and add to the SQL
while read -r line; do
                # Each line is "11019262 property=P373"
                property=`cut -d "=" -f 2 <<< "$line"`
                count=`cut -d " " -f 1 <<< "$line"`
                sql="$sql ('$dateISO', '$property', '$count'), "
done <<< "$output"

# Finish the SQL
sql=${sql::-2}
sql="$sql;"

# Commit the SQL
mysql --defaults-file=/etc/mysql/conf.d/analytics-research-client.cnf -h analytics-store.eqiad.wmnet -A -e "$sql" staging

echo "All done!"
