#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about the number of users that have the revslider feature disabled across
 * all wikis as a whole to graphite.
 * Used by: https://grafana.wikimedia.org/d/000000260/revisionslider
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'revslider-userprops' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$sectionMapper = new WikimediaDbSectionMapper();

$metrics = [];

foreach ( $dbs as $dbname ) {
	if ( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );

	$sql = 'SELECT COUNT(*) AS disables';
	$sql .= " FROM $dbname.user_properties";
	$sql .= " WHERE up_property = 'revisionslider-disable'";
	$sql .= ' AND up_value = 1';
	$queryResult = $pdo->query( $sql );

	if ( $queryResult === false ) {
		$output->outputMessage( "RevSlider DB query failed for $dbname, Skipping!!" );
	} else {
		$row = $queryResult->fetch();
		@$metrics['daily.revslider.userprops.disables.count'] += $row['disables'];
	}
}

foreach ( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
