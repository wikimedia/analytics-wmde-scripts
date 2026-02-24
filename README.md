# analytics/wmde/scripts

This repo contains scripts for collecting data for the WMDE development teams.

This repo is cloned in the `statistics::wmde` role in the wmf puppet repo.

See the [contributing guide](./CONTRIBUTING.md) for a full overview of how to contribute to this project.

## Deployment

The `master` branch is a development branch.
The `production` branch is automatically deployed and run on WMF analytics servers.
When there is no ongoing development, both branches should contain the same code.
This is normally achieved by cherry-picking each reviewed change from `master` to `production`.

## Configuration

Some of the social scripts require configuration settings to work.
These should be stored in a file called `config` in the directory above the directory of this repo.
The config keys and values are delimited with spaces.

The file should look something like the following:

```
mm-wikidata-pass password1
mm-wikidatatech-pass password2
mm-user foo@bar.baz
dump-dir /tmp/dumps
wdqs_host http://wdqs1005.eqiad.wmnet:8888
```

## Scripts execution time limit

All scripts have a maximum execution time set to **`one hour`**,
with a failure being triggered automatically afterwards.
The time limit is globally set in [lib/scriptsTimeLimit.php](./lib/scriptsTimeLimit.php) and included in all the scripts through [lib/load.php](./lib/load.php).
See [T243894](https://phabricator.wikimedia.org/T243894) for the reasons why we use this maximum execution time.
