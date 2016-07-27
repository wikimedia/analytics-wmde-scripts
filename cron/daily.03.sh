#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron at 03 hours every day.
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    echo "No argument supplied"
    exit 1
fi

# Data model
eval "$1/src/wikidata/datamodel/properties_by_datatype.php"
eval "$1/src/wikidata/datamodel/terms_by_language.php"
eval "$1/src/wikidata/datamodel/sitelinks_per_site.php"
eval "$1/src/wikidata/datamodel/sitelinks_per_item.php"
eval "$1/src/wikidata/datamodel/statements_per_entity.php"
eval "$1/src/wikidata/sparql/ranks.php"
eval "$1/src/wikidata/sparql/instanceof.php"
eval "$1/src/wikidata/wikidata-analysis/metrics.php"

# Entity usage
eval "$1/src/wikidata/entityUsage.php"

# Social
eval "$1/src/wikidata/social/facebook.php"
eval "$1/src/wikidata/social/googleplus.php"
eval "$1/src/wikidata/social/identica.php"
eval "$1/src/wikidata/social/irc.php"
eval "$1/src/wikidata/social/mail.php"
eval "$1/src/wikidata/social/newsletter.php"
eval "$1/src/wikidata/social/techmail.php"
eval "$1/src/wikidata/social/twitter.php"

# Misc
eval "$1/src/wikidata/phabricatorTasks.php"
eval "$1/src/wikidata/showcaseItems.php"
eval "$1/src/wikidata/specialEntityData.php"
eval "$1/src/wikidata/dumpDownloads.php"
eval "$1/src/catwatch/userprops.php"
eval "$1/src/betafeatures/counts.php"

# Site Stats
eval "$1/src/wikidata/site_stats/good_articles.php"
eval "$1/src/wikidata/site_stats/total_edits.php"
eval "$1/src/wikidata/site_stats/total_pages.php"
eval "$1/src/wikidata/site_stats/active_users.php"
eval "$1/src/wikidata/site_stats/users.php"
eval "$1/src/wikidata/site_stats/user_groups.php"
eval "$1/src/wikidata/site_stats/rolling_rc.php"
eval "$1/src/wikidata/site_stats/pages_by_namespace.php"
eval "$1/src/wikidata/site_stats/user_languages.php"
eval "$1/src/wikidata/site_stats/page_size.php"

