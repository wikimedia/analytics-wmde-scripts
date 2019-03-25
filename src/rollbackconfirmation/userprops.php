#!/usr/bin/php
<?php
/**
 * Sends data about the number of users that have the rollback confirmation prompt feature
 * actively disabled or enabled across all wikis as a whole to graphite.
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-rollbackconf
 */

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'rollbackconf-userprops' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$sectionMapper = new WikimediaDbSectionMapper();

$metrics = array();

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}

	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );
	if( $dbname === 'dewiki' ) {
		$sql = "SELECT COUNT(*) AS disables";
		$sql .= " FROM $dbname.user_properties";
		$sql .= " WHERE up_property = 'showrollbackconfirmation'";
		$sql .= " AND up_value = 0";
		$queryResult = $pdo->query( $sql );

		if( $queryResult === false ) {
			$output->outputMessage( "RollbackConf DB query failed for $dbname, Skipping!!" );
		} else {
			$row = $queryResult->fetch();
			@$metrics['daily.rollbackconf.userprops.disables.count'] += $row['disables'];
		}
	} else {
		$sql = "SELECT COUNT(*) AS enables";
		$sql .= " FROM $dbname.user_properties";
		$sql .= " WHERE up_property = 'showrollbackconfirmation'";
		$sql .= " AND up_value = 1";
		$queryResult = $pdo->query( $sql );

		if( $queryResult === false ) {
			$output->outputMessage( "RollbackConf DB query failed for $dbname, Skipping!!" );
		} else {
			$row = $queryResult->fetch();
			@$metrics['daily.rollbackconf.userprops.enables.count'] += $row['enables'];
		}
	}
}

foreach( $metrics as $metricName => $value ) {
	WikimediaGraphite::sendNow( $metricName, $value );
}
