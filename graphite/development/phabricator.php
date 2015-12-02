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
		$cols = array();

		$pageParts = explode( '<span class="phui-header-header">', $page );
		foreach( $pageParts as $pagePartKey => $pagePart ){
			if( $pagePartKey < 2 ) {
				continue;
			}
			$colParts = explode( '</span>', $pagePart, 2 );
			$colName = $colParts[0];
			$colCards = substr_count( $pagePart, 'data-sigil="project-card"' );
			$cols[$colName] = $colCards;
		}

		foreach( $cols as $name => $value ) {
			$name = str_replace( ' ', '_', $name );
			$metricName = 'daily.wikidata.phabricator.board.columns.' . $name;
			exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
		}
	}

}
