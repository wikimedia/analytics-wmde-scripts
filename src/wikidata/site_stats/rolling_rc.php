#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000170/wikidata-edits?viewPanel=7
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-rolling_rc' )->markStart();
$metrics = new WikidataRollingRc();
$metrics->execute();
$output->markEnd();

class WikidataRollingRc {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/rolling_rc.sql' ) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		foreach ( $rows as $row ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.rc.rolling30d',
				$row['count']
			);
		}
	}

}
