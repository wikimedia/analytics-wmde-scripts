#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Created as part of https://phabricator.wikimedia.org/T68025
 * Sends data about the number slots on some wikis to graphite.
 * Used by: https://grafana.wikimedia.org/d/pwq8ZIxWk/large-site-db-tables
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'dbtables-slots' )->markStart();

$dbs = WikimediaDbList::get( 'wikibaserepo' );

$sectionMapper = new WikimediaDbSectionMapper();

// Don't bother tracking for the 2 test wikis...
foreach ( $dbs as $dbname ) {
	if ( $dbname === 'testcommonswiki' || $dbname === 'testwikidatawiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );

	// Count each type of slot
	// Takes 2-5 mins for commonswiki on 17 July 2019 -- addshore
	$sql = 'SELECT role_name as role, COUNT(*) AS slots';
	$sql .= " FROM $dbname.slots, $dbname.slot_roles";
	$sql .= ' WHERE role_id = slot_role_id';
	$sql .= ' GROUP BY slot_role_id';
	$queryResult = $pdo->query( $sql );

	if ( $queryResult === false ) {
		$output->outputMessage( "Slots DB query failed for $dbname, Skipping!!" );
	} else {
		foreach ( $queryResult as $row ) {
			$role = $row['role'];
			$value = $row['slots'];
			$metricName = "daily.dbtables.$dbname.slots.byRole.$role";

			WikimediaGraphite::sendNow( $metricName, $value );
			WikimediaStatsdExporter::sendNow( 'daily_dbtables_slots_total', $value, [ 'name' => $dbname, 'role' => $role ] );
		}
	}
}
