# analytics/limn/wikidata-data

This repository actually has nothing to do with limn.

It instead contains a bunch of scripts collecting data for the Wikidata dashboards currently in Grafana.

All scripts in this repo have deliberately been written with NO external dependancies or libraries to mean deploying in places (such as potentially the WMF cluster) is super easy.

## Configuration

Some of the social scripts require configuration settings to work.

These should be stored in a file called 'config' in the root of this repo.

The file should look something like the below:

    facebook someHashKeyThing1
    google someHashKeyThing2
    mm-wikidata-pass password1
    mm-wikidatatech-pass password2
    mm-user foo@bar.baz

## Running the scripts

These scripts should be triggered from a cron that looks something like this:

    MAILTO=cron@domainname.org
    
    # Run minutely
    * * * * * ~/wikidata-data/minutely.sh
    * * * * * php ~/wikidata-data/graphite/rc.php
    
    # Daily
    0 3 * * * ~/wikidata-data/daily_datamodel.sh
    0 4 * * * php ~/wikidata-data/graphite/entityUsage.php
    0 5 * * * ~/wikidata-data/daily_social.sh
    0 6 * * * ~/wikidata-data/daily_site_stats.sh
    
    # Logrotate is at 6:25, + time for rsync (hourly?), 12 gives us roughly 6 hours
    # This MUST be run on stat1002
    0 12 * * * php ~/wikidata-data/graphite/api/logScanner.php

## Graphite

Metrics are currently stored in the following paths in graphite:

    wikidata.*
    daily.wikidata.*