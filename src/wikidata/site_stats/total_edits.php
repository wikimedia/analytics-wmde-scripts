#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataTotalEdits();
$metrics->execute();

class WikidataTotalEdits{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$result = $pdo->query( "select ss_total_edits from wikidatawiki.site_stats" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for total_edits" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['ss_total_edits'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.total_edits", $count );
	}

}
