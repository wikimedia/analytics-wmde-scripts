#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
Output::startScript( __FILE__ );

$metrics = new WikidataPageSizes();
$metrics->execute();

class WikidataPageSizes{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/select_page_size.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$namespace = $row['namespace'];
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.page_length.$namespace.avg",
				$row['avg']
			);
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.page_length.$namespace.max",
				$row['max']
			);

		}
	}

}
