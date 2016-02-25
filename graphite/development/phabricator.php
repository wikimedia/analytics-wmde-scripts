#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * NOTE: This may need to be updated after every phabricator update
 */

libxml_use_internal_errors( true );
require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
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
			exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
		}
	}

}
