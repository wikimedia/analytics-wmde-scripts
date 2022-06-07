#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-total_edits' )->markStart();
$metrics = new WikidataTotalEdits();
$metrics->execute();
$output->markEnd();

class WikidataTotalEdits {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$result = $pdo->query( 'select sum(ss_total_edits) as total_edits from wikidatawiki.site_stats' );

		if ( $result === false ) {
			throw new RuntimeException( 'Something went wrong with the db query for total_edits' );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['total_edits'];
		WikimediaGraphite::sendNow( 'daily.wikidata.site_stats.total_edits', $count );
	}

}
