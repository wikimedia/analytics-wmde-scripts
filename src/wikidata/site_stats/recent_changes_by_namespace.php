#!/usr/bin/php
<?php

/**
 * Count edits in entity and associated talk namespaces on Wikidata.org
 * and send them to Graphite.
 * Used by: no board yet
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-recent_changes_by_namespace' )->markStart();
$metrics = new WikidataRecentChangesByNamespace();
$metrics->execute( $output );
$output->markEnd();

class WikidataRecentChangesByNamespace {

	public function execute( Output $output ) {
		$dateTimeFrom = new DateTime( 'midnight 1 day ago UTC' );
		$dateTimeTo = new DateTime( 'midnight today UTC' );
		$editsByNamespace = [
			0 => 0, # (Item)
			1 => 0, # Talk (item talk)
			120 => 0, # Property
			121 => 0, # Property talk
			146 => 0, # Lexeme
			147 => 0, # Lexeme talk
			640 => 0, # Schema
			641 => 0, # Schema talk
		];

		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$pdoStatement = $pdo->prepare( file_get_contents( __DIR__ . '/sql/recent_changes_by_namespace.sql' ) );
		$queryResult = $pdoStatement->execute( array_merge(
			[
				$dateTimeFrom->format( 'YmdHis' ),
				$dateTimeTo->format( 'YmdHis' ),
			],
			array_keys( $editsByNamespace )
		) );

		if ( $queryResult === false ) {
			$output->outputMessage( 'DB query failed:' );
			$output->outputMessage( var_export( $pdoStatement->errorInfo(), true ) );
			$output->outputMessage( 'Skipping!' );
			return;
		}

		while ( $row = $pdoStatement->fetch( PDO::FETCH_ASSOC ) ) {
			$editsByNamespace[$row['namespace']] = $row['count'];
		}

		foreach ( $editsByNamespace as $namespace => $count ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.edits_by_namespace.' . $namespace,
				$count
			);
		}
	}

}
