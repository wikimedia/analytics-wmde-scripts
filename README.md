# analytics/wmde/scripts

This repo contains a bunch of scripts collecting data for the WMDE development teams.

This repo is cloned in the statistics::wmde role in the wmf puppet repo.

## Deployment

The master branch is a development branch.
The production branch is automatically deployed and run on WMF analytics servers.

## Configuration

Some of the social scripts require configuration settings to work.

These should be stored in a file called 'config' in the directory above the directory of this repo.
The config keys and values are delimited with spaces.

The file should look something like the below:

    mm-wikidata-pass password1
    mm-wikidatatech-pass password2
    mm-user foo@bar.baz
    dump-dir /tmp/dumps
    wdqs_host http://wdqs1005.eqiad.wmnet:8888

## Graphite

Metrics are currently stored in the following paths in graphite:

    wikidata.*
    daily.wikidata.*

The paths to **statsd.eqiad.wmnet** and **graphite.eqiad.wmnet** are hardcoded everywhere.
