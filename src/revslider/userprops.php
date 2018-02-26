#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about the number of users that have the revslider feature disabled across
 * all wikis as a whole to graphite.
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-revisionslider
 */

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'revslider-userprops' )->markStart();

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/dblists/all.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for RevSlider tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

$metrics = array();

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$sql = "SELECT COUNT(*) AS disables";
	$sql .= " FROM $dbname.user_properties";
	$sql .= " WHERE up_property = 'revisionslider-disable'";
	$sql .= " AND up_value = 1";
	$queryResult = $pdo->query( $sql );

	if( $queryResult === false ) {
		$output->outputMessage( "RevSlider DB query failed for $dbname, Skipping!!" );
	} else {
		$row = $queryResult->fetch();
		@$metrics['daily.revslider.userprops.disables.count'] += $row['disables'];
	}
}

foreach( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
