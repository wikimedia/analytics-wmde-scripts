#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about the number of users that have the catwatch feature enabled by default across
 * all wikis as a whole to graphite along side the number of wikis that have at least 1 user
 * using the feature by default.
 * Used by: https://grafana.wikimedia.org/d/000000189/catwatch-feature
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'catwatch-userprops' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$sectionMapper = new WikimediaDbSectionMapper();

$metrics = [];

foreach ( $dbs as $dbname ) {
	if ( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );

	$thisWikiHasSomeUse = false;
	// Count each type of entity usage
	$sql = 'SELECT up_property AS settingAtZero, COUNT(up_user) AS users';
	$sql .= " FROM $dbname.user_properties";
	$sql .= " WHERE up_property IN ( 'hidecategorization', 'watchlisthidecategorization' )";
	$sql .= ' AND up_value = 0';
	$sql .= ' GROUP BY up_property';
	$queryResult = $pdo->query( $sql );

	if ( $queryResult === false ) {
		$output->outputMessage( "CatWatch DB query failed for $dbname, Skipping!!" );
	} else {

		foreach ( $queryResult as $row ) {
			if ( $row['settingAtZero'] == 'hidecategorization' ) {
				if ( $row['users'] > 0 ) {
					$thisWikiHasSomeUse = true;
					@$metrics['daily.catwatch.userprops.recentchanges.count'] += $row['users'];
				}
			} elseif ( $row['settingAtZero'] == 'watchlisthidecategorization' ) {
				if ( $row['users'] > 0 ) {
					$thisWikiHasSomeUse = true;
					@$metrics['daily.catwatch.userprops.watchlist.count'] += $row['users'];
				}
			}
		}

		if ( $thisWikiHasSomeUse ) {
			@$metrics['daily.catwatch.wikis.used'] += 1;
		} else {
			@$metrics['daily.catwatch.wikis.notused'] += 1;
		}

	}
}

foreach ( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
