#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends data about the number of tasks in the wikidata project on phabricator and their state on
 * the main wikidata workboard (which column they are in)
 * NOTE: This may need to be updated after every phabricator update
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-tasks
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-phabricatorTasks' )->markStart();

libxml_use_internal_errors( true );
$metrics = new WikidataPhabricator();
$metrics->execute();

class WikidataPhabricator {

	public function execute() {
		$response = WikimediaCurl::curlGetWithRetryExternal( 'https://phabricator.wikimedia.org/tag/wikidata/' );
		$page = $response[1];
		$page = htmlspecialchars_decode( $page );

		$colNames = [];
		$colCounts = [];

		$headerParts = explode( '<div class="phui-header-header">', $page );
		foreach ( $headerParts as $headerPartKey => $headerPart ) {
			if ( $headerPartKey == 0 ) {
				continue;
			}
			$innerHeaderParts = explode( '</div>', $headerPart, 2 );
			$colNames[] = $innerHeaderParts[0];
		}

		$dataParts = explode( '"columnMaps":', $page, 2 );
		$dataParts = explode( '"orderMaps":', $dataParts[1], 2 );
		$columnMaps = trim( $dataParts[0], ' ,' );
		$columnMaps = json_decode( $columnMaps, true );
		foreach ( $columnMaps as $values ) {
			$colCounts[] = count( $values );
		}

		// Note: This makes the assumption that the cols are in the same order in the data fields as on the workboard
		foreach ( $colNames as $key => $name ) {
			$name = str_replace( ' ', '_', $name );
			$value = $colCounts[$key];
			$metricName = 'daily.wikidata.phabricator.board.columns.' . $name;
			WikimediaGraphite::sendNow( $metricName, $value );
		}

		$this->countPriorities( $page );
	}

	/**
	 * @param string $page HTML
	 */
	private function countPriorities( $page ) {
		static $priorities = [
			'pink' => 'unbreak',
			'violet' => 'triage',
			'red' => 'high',
			'orange' => 'normal',
			'yellow' => 'low',
			'sky' => 'lowest',
		];

		preg_match_all(
			'/\bclass=\\\\?"[^"]*\bphui-oi-bar-color-(' . implode( '|', array_keys( $priorities ) ) . ')\b/',
			$page,
			$matches
		);
		$counts = array_count_values( $matches[1] );

		foreach ( $priorities as $color => $name ) {
			$metricName = 'daily.wikidata.phabricator.board.priorities.' . $name;
			if ( array_key_exists( $color, $counts ) ) {
				$value = $counts[$color];
			} else {
				// If there are no tasks matches, still submit a value
				$value = 0;
			}
			WikimediaGraphite::sendNow( $metricName, $value );
		}
	}

}

$output->markEnd();
