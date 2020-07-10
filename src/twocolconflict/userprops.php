#!/usr/bin/php
<?php
/**
 *
 * @author Christoph Jauera
 * Sends data about the number of unique users that have the TwoColConflict feature disabled
 * at at least one wiki to graphite.
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-twocolconflict
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'twocolconflict-userprops' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$sectionMapper = new WikimediaDbSectionMapper();

$values = [];

foreach ( $dbs as $dbname ) {
	if ( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );

	$sql = "
		SELECT user_name
		FROM $dbname.user_properties
		JOIN $dbname.user ON up_user = user_id
		WHERE
			(up_property = 'twocolconflict-enabled' OR up_property = 'twocolconflict')
			AND up_value = 0
	";
	$queryResult = $pdo->query( $sql );

	if ( $queryResult === false ) {
		$output->outputMessage( "TwoColConflict DB query failed for $dbname, Skipping!!" );
	} else {
		foreach ( $queryResult as $row ) {
			$values[ $row['user_name'] ] = 1;
		}
	}
}

WikimediaGraphite::sendNow( 'daily.twocolconflict.userprops.all_disables.count', count( $values ) );
