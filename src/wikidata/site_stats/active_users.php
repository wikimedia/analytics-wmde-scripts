#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-active_users' )->markStart();
$metrics = new WikidataActiveUsers();
$metrics->execute();
$output->markEnd();

class WikidataActiveUsers{

	public function execute() {
		$pdo = WikimediaDb::getPdo();

		if( $pdo->query( file_get_contents( __DIR__ . '/sql/tmptbl_active_user_changes.sql' ) ) === false ) {
			throw new RuntimeException( "Failed to run file active_user_changes sql" );
		}

		$results = array();
		$results[1] = $pdo->query( "SELECT COUNT(*) AS users FROM staging.active_user_changes WHERE changes >= 1" );
		$results[5] = $pdo->query( "SELECT COUNT(*) AS users FROM staging.active_user_changes WHERE changes >= 5" );
		$results[100] = $pdo->query( "SELECT COUNT(*) AS users FROM staging.active_user_changes WHERE changes >= 100" );

		foreach( $results as $changeCount => $result ) {
			/** @var PDOStatement $result */
			if( $result === false ) {
				throw new RuntimeException( "Something went wrong with the db query for changeCount: $changeCount" );
			}
			$rows = $result->fetchAll();
			$users = $rows[0]['users'];
			WikimediaGraphite::sendNow( "daily.wikidata.site_stats.active_users.$changeCount", $users );
		}
	}

}
