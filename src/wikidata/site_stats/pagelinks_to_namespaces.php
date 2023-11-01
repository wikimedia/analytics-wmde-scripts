#!/usr/bin/php
<?php

/**
 * Count links to entity and Schema namespaces on Wikidata.org
 * and send them to Graphite.
 * Used by: https://grafana.wikimedia.org/d/000000162/wikidata-site-stats?viewPanel=18
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-pagelinks_to_namespaces' )->markStart();
$metrics = new WikidataPagelinksToNamespaces();
$metrics->execute( $output );
$output->markEnd();

class WikidataPagelinksToNamespaces {

	public function execute( Output $output ) {
		$pagelinksToNamespace = [
			0 => 0, # (Item)
			120 => 0, # Property
			146 => 0, # Lexeme
			640 => 0, # Schema
		];

		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$pdoStatement = $pdo->prepare( file_get_contents( __DIR__ . '/sql/pagelinks_to_namespaces.sql' ) );
		$queryResult = $pdoStatement->execute( array_keys( $pagelinksToNamespace ) );

		if ( $queryResult === false ) {
			$output->outputMessage( 'DB query failed:' );
			$output->outputMessage( var_export( $pdoStatement->errorInfo(), true ) );
			$output->outputMessage( 'Skipping!' );
			return;
		}

		while ( $row = $pdoStatement->fetch( PDO::FETCH_ASSOC ) ) {
			$pagelinksToNamespace[$row['namespace']] = $row['count'];
		}

		foreach ( $pagelinksToNamespace as $namespace => $count ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.pagelinks_to_namespace.' . $namespace,
				$count
			);
		}
	}

}
