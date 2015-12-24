#!/usr/bin/php
<?php
/**
 * This does not actually relate to wikidata.
 * This tracks the usage of the catwatch feature every day across all wikis.
 *
 * TODO FIXME this should probably live somewhere else?
 * But it uses the WikimediaDb and WikimediaCurl classes. So we can just leave it here....
 *
 * @author Addshore
 */

require_once( __DIR__ . '/../src/WikimediaDb.php' );
require_once( __DIR__ . '/../src/WikimediaCurl.php' );

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/all.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for CatWatch tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

$metrics = array();

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' ) {
		continue;
	}
	$thisWikiHasSomeUse = false;
	// Count each type of entity usage
	$sql = "SELECT up_property AS settingAtZero, COUNT(up_user) AS users";
	$sql .= " FROM $dbname.user_properties";
	$sql .= " WHERE up_property IN ( 'hidecategorization', 'watchlisthidecategorization' )";
	$sql .= " AND up_value = 0";
	$sql .= " GROUP BY up_property";
	$queryResult = $pdo->query( $sql );

	if( $queryResult === false ) {
		echo "CatWatch DB query failed for $dbname, Skipping!!\n";
	} else {

		foreach( $queryResult as $row ) {
			if( $row['settingAtZero'] == 'hidecategorization' ) {
				if( $row['users'] > 0 ) {
					$thisWikiHasSomeUse = true;
					@$metrics['daily.catwatch.userprops.recentchanges.count'] += $row['users'];
				}
			} elseif( $row['settingAtZero'] == 'watchlisthidecategorization' ) {
				if( $row['users'] > 0 ) {
					$thisWikiHasSomeUse = true;
					@$metrics['daily.catwatch.userprops.watchlist.count'] += $row['users'];
				}
			}
		}

		if( $thisWikiHasSomeUse ) {
			@$metrics['daily.catwatch.wikis.used'] += 1;
		} else {
			@$metrics['daily.catwatch.wikis.notused'] += 1;
		}

	}
}

foreach( $metrics as $metricName => $value ) {
	exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
}
