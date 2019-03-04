#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-pages_by_namespace' )->markStart();
$metrics = new WikidataPagesByNamespace();
$metrics->execute();
$output->markEnd();

class WikidataPagesByNamespace {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_pages_by_namespace.sql'
		) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		$namespaceTotals = [ 0 => 0, 1 => 0, 120 => 0, 146 => 0, 640 => 0 ];
		foreach ( $rows as $row ) {
			$namespace = $row['namespace'];
			$type = $row['redirect'] == 1 ? 'redirects' : 'nonredirects';

			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.$type",
				$row['count']
			);

			$namespaceTotals[$row['namespace']] += $row['count'];
		}

		foreach ( $namespaceTotals as $namespace => $total ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.total",
				$total
			);
		}
	}

}
