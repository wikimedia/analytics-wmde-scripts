#!/bin/bash -x
# @author Addshore
#
# This script should be run through cron at 03 hours every day.
# The first parameter should be the directory this repo is checked out into.
# Scripts that are run here need to be executable (+x) in git.

if [ -z "$1" ]
  then
    date +"%F %T daily.03.sh No argument supplied!"
    exit 1
fi

date +"%F %T daily.03.sh Started!"

# Wikidata Data model
eval "$1/src/wikidata/datamodel/sitelinks_per_item.php" &
eval "$1/src/wikidata/datamodel/statements_per_entity.php" &
eval "$1/src/wikidata/datamodel/sitelinks_per_site.php" &
eval "$1/src/wikidata/datamodel/properties_by_datatype.php" &
eval "$1/src/wikidata/sparql/ranks.php" &
eval "$1/src/wikidata/sparql/instanceof.php" &
eval "$1/src/wikidata/sparql/constraintsByType.php" &
eval "$1/src/wikidata/dumpScanProcessing.php" &

# Wikidata client entity usage
eval "$1/src/wikidata/entityUsage.php" &

# Wikidata Social
eval "$1/src/wikidata/social/facebook.php" &
eval "$1/src/wikidata/social/identica.php" &
eval "$1/src/wikidata/social/irc.php" &
eval "$1/src/wikidata/social/mail.php" &
eval "$1/src/wikidata/social/newsletter.php" &
eval "$1/src/wikidata/social/techmail.php" &
eval "$1/src/wikidata/social/twitter.php" &

# Wikidata site stats
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
eval "$1/src/wikidata/site_stats/lexemes.php" &
eval "$1/src/wikidata/site_stats/recent_changes_by_namespace.php" &
eval "$1/src/wikidata/site_stats/pagelinks_to_namespaces.php" &

# Misc
eval "$1/src/wikibase/dockerStats.php" &
eval "$1/src/wikidata/phabricatorTasks.php" &
eval "$1/src/wikidata/showcaseItems.php" &
eval "$1/src/wikidata/dumpDownloads.php" &
eval "$1/src/advancedsearch/userprops.php" &
eval "$1/src/betafeatures/counts.php" &
eval "$1/src/catwatch/userprops.php" &
eval "$1/src/echo/statusNotifications.php" &
eval "$1/src/revslider/userprops.php" &
eval "$1/src/rollbackconfirmation/userprops.php" &
eval "$1/src/twocolconflict/userprops.php" &

date +"%F %T daily.03.sh Waiting!"
wait
date +"%F %T daily.03.sh Ended!"

