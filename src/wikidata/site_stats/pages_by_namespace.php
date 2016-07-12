#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataPagesByNamespace();
$metrics->execute();

class WikidataPagesByNamespace{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_pages_by_namespace.sql'
		) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		$namespaceTotals = array( 0 => 0, 1 => 0, 120 => 0 );
		foreach( $rows as $rowNumber => $row ) {
			$namespace = $row['namespace'];
			$type = $row['redirect'] == 1 ? 'redirects' : 'nonredirects';

			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.$type",
				$row['count']
			);

			$namespaceTotals[$row['namespace']] += $row['count'];
		}

		foreach( $namespaceTotals as $namespace => $total ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.total",
				$total
			);
		}
	}

}
