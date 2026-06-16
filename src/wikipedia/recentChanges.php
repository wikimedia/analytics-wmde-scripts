#!/usr/bin/php
<?php

/**
 * Gather data about the recent changes on pilot Wikis.
 * See T422564 and T426384 for more information.
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikipedia-recent_changes' )->markStart();
$metrics = new WikipediaRecentChanges;
$metrics->execute( $output );
$output->markEnd();

class WikipediaRecentChanges {

	private const WIKIS = [
		'enwiki', # English
		'dewiki', # German
		'frwiki', # French
		'brwiki', # Breton
		'svwiki', # Swedish
		'euwiki', # Basque
		'arwiki'  # Arabic
	];

	public function execute( Output $output ) {
		$sqlTemplate = file_get_contents( __DIR__ . '/sql/wiki_recent_changes.sql' );

		foreach ( self::WIKIS as $wiki ) {
			$pdo = WikimediaDb::getPdoNewHosts( $wiki, new WikimediaDbSectionMapper() );
			$sql = str_replace( '{{WIKI}}', $wiki, $sqlTemplate );

			$pdoStatement = $pdo->prepare( $sql );
			$queryResult = $pdoStatement->execute();

			if ( $queryResult === false ) {
				$output->outputMessage( "DB query failed for {$wiki}:" );
				$output->outputMessage( var_export( $pdoStatement->errorInfo(), true ) );
				$output->outputMessage( 'Skipping!' );
				continue;
			}

			$row = $pdoStatement->fetch( PDO::FETCH_ASSOC );

			// Send everything to Prometheus!
			$this->sendMetricToPrometheus( 'recent_changes_all_total', $row['total_changes'], [ 'wiki' => $wiki ] );
			$this->sendMetricToPrometheus( 'recent_changes_wikidata_total', $row['wikidata_changes'], [ 'wiki' => $wiki ] );
		}
	}

	private function sendMetricToPrometheus( mixed $name, mixed $value, $labels = [] ) {
		WikimediaStatsdExporter::sendNow( $name, $value, $labels );
	}
}
