#!/usr/bin/php
<?php

/**
 * @author sihe
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-active_users_by_namespace' )->markStart();
$metrics = new WikidataActiveUsersByNamespace();
$metrics->execute();
$output->markEnd();

class WikidataActiveUsersByNamespace {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );

		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/active_user_changes_by_namespace.sql' ) );
		if ( $queryResult === false ) {
			throw new RuntimeException( 'Failed to run file active_user_changes_by_namespace sql' );
		}

		$results = [];
		foreach ( $this->collectNamespaces( $queryResult ) as $namespace ) {
			$results[ $namespace ] = [ 1 => 0, 5 => 0, 100 => 0 ];
		}

		foreach ( $queryResult as $row ) {
			$namespace = (int)$row['namespace'];
			$changes = (int)$row['changes'];
			if ( $changes >= 100 ) {
				$results[$namespace][100] += 1;
				$results[$namespace][5] += 1;
				$results[$namespace][1] += 1;
			} elseif ( $changes >= 5 ) {
				$results[$namespace][5] += 1;
				$results[$namespace][1] += 1;
			} elseif ( $changes >= 1 ) {
				$results[$namespace][1] += 1;
			}
		}

		foreach ( $results as $namespace => $userCount ) {
			foreach ( $userCount as $changeCount => $users ) {
				WikimediaGraphite::sendNow(
						"daily.wikidata.site_stats.active_users_by_namespace.$namespace.$changeCount",
						$users
				);
			}
		}
	}

	private function collectNamespaces( array $rows ) {
		$namespaces = [];
		foreach ( $rows as $row ) {
			$namespaces[ $row['namespace'] ] = 1;
		}

		return array_keys( $namespaces );
	}
}
