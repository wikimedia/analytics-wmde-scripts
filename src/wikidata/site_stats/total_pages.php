#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000162/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-total_pages' )->markStart();
$metrics = new WikidataTotalPages();
$metrics->execute();
$output->markEnd();

class WikidataTotalPages {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$result = $pdo->query( 'select sum(ss_total_pages) as total_pages from wikidatawiki.site_stats' );

		if ( $result === false ) {
			throw new RuntimeException( 'Something went wrong with the db query for total_pages' );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['total_pages'];
		WikimediaGraphite::sendNow( 'daily.wikidata.site_stats.total_pages', $count );
		WikimediaStatsdExporter::sendNow( 'daily_wikidata_siteStats_pages_total', $count );
	}

}
