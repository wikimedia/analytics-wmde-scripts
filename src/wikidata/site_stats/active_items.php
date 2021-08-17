#!/usr/bin/php
<?php

/**
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-active_items' )->markStart();
$metrics = new WikidataActiveItems();
$metrics->execute();
$output->markEnd();

class WikidataActiveItems {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );

		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/active_items.sql' ) );
		if ( $queryResult === false ) {
			throw new RuntimeException( 'Failed to run file active_items sql' );
		}

		foreach ( $queryResult as $row ) { // we only expect one row
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.active_items.1', // items with at least 1 edit
				$row['count']
			);
		}
	}

}
