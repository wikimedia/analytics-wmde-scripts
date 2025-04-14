#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000167/wikidata-datamodel
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-page_size' )->markStart();
$metrics = new WikidataPageSizes();
$metrics->execute();
$output->markEnd();

class WikidataPageSizes {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/select_page_size.sql' ) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		foreach ( $rows as $row ) {
			$namespace = $row['namespace'];
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.page_length.$namespace.avg",
				$row['avg']
			);
			WikimediaStatsdExporter::sendNow( 'daily_wikidata_siteStats_pageLength_avg', $row['avg'], [ 'namespace' => $namespace ] );
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.page_length.$namespace.max",
				$row['max']
			);
			WikimediaStatsdExporter::sendNow( 'daily_wikidata_siteStats_pageLength_max', $row['max'], [ 'namespace' => $namespace ] );
		}
	}

}
