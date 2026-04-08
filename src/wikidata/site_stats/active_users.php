#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000162/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-active_users' )->markStart();
$metrics = new WikidataActiveUsers();
$metrics->execute();
$output->markEnd();

class WikidataActiveUsers {

	private function runQueryAndSendMetrics( $pdo, $sqlFile, $metricName ) {
		$queryResult = $pdo->query( file_get_contents( $sqlFile ) );
		if ( $queryResult === false ) {
			throw new RuntimeException( 'Failed to run file ' . basename( $sqlFile ) );
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
			WikimediaStatsdExporter::sendNow(
				$metricName,
				$users,
				[ 'changeCount' => $changeCount ]
			);
		}
	}

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );

		$this->runQueryAndSendMetrics(
			$pdo,
			__DIR__ . '/sql/active_user_changes/permanent_user.sql',
			'daily_wikidata_siteStats_activeUsers_total'
		);

		$this->runQueryAndSendMetrics(
			$pdo,
			__DIR__ . '/sql/active_user_changes/temporary_user.sql',
			'daily_wikidata_siteStats_activeTemporaryAccounts_total'
		);
	}
}
