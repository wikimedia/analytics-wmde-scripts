#!/usr/bin/php
<?php
/**
 *
 * @author Christoph Jauera
 * Sends data about the number of users that have the TwoColConflict feature disabled across
 * all wikis as a whole to graphite.
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-twocolconflict
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'twocolconflict-userprops' )->markStart();

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
	$sql .= " WHERE up_property = 'twocolconflict-enabled'";
	$sql .= ' AND up_value = 0';
	$queryResult = $pdo->query( $sql );

	if ( $queryResult === false ) {
		$output->outputMessage( "TwoColConflict DB query failed for $dbname, Skipping!!" );
	} else {
		$row = $queryResult->fetch();
		@$metrics['daily.twocolconflict.userprops.disables.count'] += $row['disables'];
	}
}

foreach ( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
