#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron at 03 hours every day.
# The first parameter should be the directory this repo is checked out into.

if [ -z "$1" ]
  then
    date +"%F %T daily.03.sh No argument supplied!"
    exit 1
fi
date +"%F %T daily.03.sh Started!"

# Data model
date +"%F %T daily.03.sh Wikidata datamodel scripts running!"
eval "$1/src/wikidata/datamodel/properties_by_datatype.php"
eval "$1/src/wikidata/datamodel/terms_by_language.php"
eval "$1/src/wikidata/datamodel/sitelinks_per_site.php"
eval "$1/src/wikidata/datamodel/sitelinks_per_item.php"
eval "$1/src/wikidata/datamodel/statements_per_entity.php"
eval "$1/src/wikidata/sparql/ranks.php"
eval "$1/src/wikidata/sparql/instanceof.php"
eval "$1/src/wikidata/wikidata-analysis/metrics.php"
date +"%F %T daily.03.sh Wikidata datamodel scripts complete!"

# Entity usage
eval "$1/src/wikidata/entityUsage.php"

# Social
date +"%F %T daily.03.sh Social scripts running!"
eval "$1/src/wikidata/social/facebook.php" &
eval "$1/src/wikidata/social/googleplus.php" &
eval "$1/src/wikidata/social/identica.php" &
eval "$1/src/wikidata/social/irc.php" &
eval "$1/src/wikidata/social/mail.php" &
eval "$1/src/wikidata/social/newsletter.php" &
eval "$1/src/wikidata/social/techmail.php" &
eval "$1/src/wikidata/social/twitter.php" &
date +"%F %T daily.03.sh Social scripts waiting!"
wait
date +"%F %T daily.03.sh Social scripts complete!"

# Misc
date +"%F %T daily.03.sh Misc scripts running!"
eval "$1/src/wikidata/phabricatorTasks.php" &
eval "$1/src/wikidata/showcaseItems.php" &
eval "$1/src/wikidata/specialEntityData.php" &
eval "$1/src/wikidata/dumpDownloads.php" &
eval "$1/src/catwatch/userprops.php" &
eval "$1/src/betafeatures/counts.php" &
date +"%F %T daily.03.sh Misc scripts waiting!"
wait
date +"%F %T daily.03.sh Misc scripts complete!"

# Site Stats
date +"%F %T daily.03.sh Wikidata site_stats scripts running!"
eval "$1/src/wikidata/site_stats/good_articles.php" &
eval "$1/src/wikidata/site_stats/total_edits.php" &
eval "$1/src/wikidata/site_stats/total_pages.php" &
eval "$1/src/wikidata/site_stats/active_users.php" &
eval "$1/src/wikidata/site_stats/users.php" &
eval "$1/src/wikidata/site_stats/user_groups.php" &
eval "$1/src/wikidata/site_stats/rolling_rc.php" &
eval "$1/src/wikidata/site_stats/pages_by_namespace.php" &
eval "$1/src/wikidata/site_stats/page_size.php" &
eval "$1/src/wikidata/site_stats/user_languages.php" &
date +"%F %T daily.03.sh Wikidata site_stats scripts waiting!"
wait
date +"%F %T daily.03.sh Wikidata site_stats scripts complete!"

wait
date +"%F %T daily.03.sh Ended!"

