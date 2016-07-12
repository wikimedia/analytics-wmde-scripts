#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataTotalPages();
$metrics->execute();

class WikidataTotalPages{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$result = $pdo->query( "select ss_total_pages from site_stats" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for total_pages" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['ss_total_pages'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.total_pages", $count );
	}

}
