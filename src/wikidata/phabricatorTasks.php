#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends data about the number of tasks in the wikidata project on phabricator and their state on
 * the main wikidata workboard (which column they are in)
 * NOTE: This may need to be updated after every phabricator update
 * Used by: https://grafana.wikimedia.org/d/000000172/wikidata-tasks
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
		$data = explode( '<data data-javelin-init-kind="behaviors" data-javelin-init-data="', $page );
		$data = explode( '"></data>', $data[2] )[0];
		$data = json_decode( $data, true );
		foreach ( $data['project-boards'][0]['columnTemplates'] as $column ) {
			$name = $column['effects'][0]['content'];
			$name = str_replace( ' ', '_', $name );
			$metricName = 'daily.wikidata.phabricator.board.columns.' . $name;
			$cards = count( $column['cardPHIDs'] );
			WikimediaGraphite::sendNow( $metricName, $cards );
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
