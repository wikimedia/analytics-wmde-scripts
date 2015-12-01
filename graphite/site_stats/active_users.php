#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$metrics = new WikidataActiveUsers();
$metrics->execute();

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
			$this->sendMetric( "daily.wikidata.site_stats.active_users.$changeCount", $users );
		}
	}


	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
