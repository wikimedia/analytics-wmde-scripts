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

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );

		$userQueryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/changes/active_user_changes.sql' ) );
		if ( $userQueryResult === false ) {
			throw new RuntimeException( 'Failed to run file active_user_changes.sql' );
		}

		$userResults = [ 1 => 0, 5 => 0, 100 => 0 ];
		foreach ( $userQueryResult as $row ) {
			$userChanges = (int)$row['changes'];
			if ( $userChanges >= 100 ) {
				$userResults[100] += 1;
				$userResults[5] += 1;
				$userResults[1] += 1;
			} elseif ( $userChanges >= 5 ) {
				$userResults[5] += 1;
				$userResults[1] += 1;
			} elseif ( $userChanges >= 1 ) {
				$userResults[1] += 1;
			}
		}

		foreach ( $userResults as $changeCount => $users ) {
			WikimediaStatsdExporter::sendNow(
				'daily_wikidata_siteStats_activeUsers_total',
				$users,
				[ 'changeCount' => $changeCount ]
			);
		}

		$tempAccountQueryResult = $pdo->query(
			file_get_contents( __DIR__ . '/sql/changes/active_temporary_account_changes.sql' )
		);
		if ( $tempAccountQueryResult === false ) {
			throw new RuntimeException( 'Failed to run file active_temporary_account_changes.sql' );
		}

		$tempAccountResults = [ 1 => 0, 5 => 0, 100 => 0 ];
		foreach ( $tempAccountQueryResult as $row ) {
			$tempAccountChanges = (int)$row['changes'];
			if ( $tempAccountChanges >= 100 ) {
				$tempAccountResults[100] += 1;
				$tempAccountResults[5] += 1;
				$tempAccountResults[1] += 1;
			} elseif ( $tempAccountChanges >= 5 ) {
				$tempAccountResults[5] += 1;
				$tempAccountResults[1] += 1;
			} elseif ( $tempAccountChanges >= 1 ) {
				$tempAccountResults[1] += 1;
			}
		}

		foreach ( $tempAccountResults as $changeCount => $tempAccounts ) {
			WikimediaStatsdExporter::sendNow(
				'daily_wikidata_siteStats_activeTemporaryAccounts_total',
				$tempAccounts,
				[ 'changeCount' => $changeCount ]
			);
		}
	}
}
