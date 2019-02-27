#!/usr/bin/php
<?php
/**
 *
 * @author Christoph Jauera
 * Sends data about the number of users that have the AdvancedSearch feature disabled across
 * all wikis as a whole to graphite.
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-advancedsearch
 */

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'advancedsearch-userprops' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$sectionMapper = new WikimediaDbSectionMapper();

$metrics = array();

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );

	$sql = "SELECT COUNT(*) AS disables";
	$sql .= " FROM $dbname.user_properties";
	$sql .= " WHERE up_property = 'advancedsearch-disable'";
	$sql .= " AND up_value = 1";
	$queryResult = $pdo->query( $sql );

	if( $queryResult === false ) {
		$output->outputMessage( "AdvancedSearch DB query failed for $dbname, Skipping!!" );
	} else {
		$row = $queryResult->fetch();
		@$metrics['daily.advancedsearch.userprops.disables.count'] += $row['disables'];
	}
}

foreach( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
