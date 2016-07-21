#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends data about the number of tasks in the wikidata project on phabricator and their state on
 * the main wikidata workboard (which column they are in)
 * NOTE: This may need to be updated after every phabricator update
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-tasks
 */

require_once( __DIR__ . '/../../lib/load.php' );
Output::startScript( __FILE__ );

libxml_use_internal_errors( true );
$metrics = new WikidataPhabricator();
$metrics->execute();

class WikidataPhabricator{

	public function execute() {
		$response = WikimediaCurl::retryingCurlGet( 'http://phabricator.wikimedia.org/tag/wikidata/', true );
		$page = $response[1];

		$colNames = array();
		$colCounts = array();

		$headerParts = explode( '<span class="phui-header-header">', $page );
		foreach( $headerParts as $headerPartKey => $headerPart ){
			if( $headerPartKey == 0 ) {
				continue;
			}
			$innerHeaderParts = explode( '</span>', $headerPart, 2 );
			$colNames[] = $innerHeaderParts[0];
		}

		$dataParts = explode( '"columnMaps":', $page );
		$dataParts = explode( '"orderMaps":', $dataParts[1] );
		$columnMaps = trim( $dataParts[0], " ," );
		$columnMaps = json_decode( $columnMaps, true );
		foreach( $columnMaps as $values ) {
			$colCounts[] = count( $values );
		}

		//Note: This makes the assumption that the cols are in the same order in the data fields as on the workboard
		foreach( $colNames as $key => $name ) {
			$name = str_replace( ' ', '_', $name );
			$value = $colCounts[$key];
			$metricName = 'daily.wikidata.phabricator.board.columns.' . $name;
			WikimediaGraphite::sendNow( $metricName, $value );
		}
	}

}
