# analytics/wmde/scripts

This repo contains a bunch of scripts collecting data for the Wikidata dashboards currently in Grafana.

All scripts in this repo have deliberately been written with NO external dependencies or libraries to mean deploying in places (such as potentially the WMF cluster) is super easy.

## Configuration

Some of the social scripts require configuration settings to work.

These should be stored in a file called 'config' in the root of this repo.

The file should look something like the below:

    facebook someHashKeyThing1
    google someHashKeyThing2
    mm-wikidata-pass password1
    mm-wikidatatech-pass password2
    mm-user foo@bar.baz

## Graphite

Metrics are currently stored in the following paths in graphite:

    wikidata.*
    daily.wikidata.*