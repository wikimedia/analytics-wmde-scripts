#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-active_users' )->markStart();
$metrics = new WikidataActiveUsers();
$metrics->execute();
$output->markEnd();

class WikidataActiveUsers {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );

		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/active_user_changes.sql' ) );
		if ( $queryResult === false ) {
			throw new RuntimeException( 'Failed to run file active_user_changes sql' );
		}

		$results = [ 1 => 0, 5 => 0, 100 => 0 ];
		foreach ( $queryResult as $row ) {
			$changes = (int)$row['changes'];
			if ( $changes >= 100 ) {
				$results[100] += 1;
				$results[5] += 1;
				$results[1] += 1;
			} elseif ( $changes >= 5 ) {
				$results[5] += 1;
				$results[1] += 1;
			} elseif ( $changes >= 1 ) {
				$results[1] += 1;
			}
		}

		foreach ( $results as $changeCount => $users ) {
			WikimediaGraphite::sendNow( "daily.wikidata.site_stats.active_users.$changeCount", $users );
		}
	}

}
