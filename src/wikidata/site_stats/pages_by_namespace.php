#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000167/wikidata-datamodel
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

		foreach ( $rows as $row ) {
			$namespace = $row['namespace'];
			$type = $row['redirect'] == 1 ? 'redirects' : 'nonredirects';

			WikimediaStatsdExporter::sendNow(
				'daily_wikidata_siteStats_pagesByNamespace_total',
				$row['count'],
				[ 'namespace' => $namespace, 'type' => $type ]
			);
		}
	}

}
