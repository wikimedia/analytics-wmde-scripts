#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends data about the number of showcase items on Wikidata to graphite based on the
 * Wikidata:Showcase_items page
 * Used by: https://grafana.wikimedia.org/d/000000162/wikidata-site-stats
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-showcaseItems' )->markStart();

$url = 'https://www.wikidata.org/w/api.php?action=query&prop=revisions&format=json&titles=Wikidata:Showcase_items&rvprop=content';
$json = WikimediaCurl::curlGetExternal( $url );

if ( $json === false ) {
	throw new RuntimeException( 'Failed to get showcase items from API' );
}

$json = json_decode( $json[1], true );
$pageArray = array_pop( $json['query']['pages'] );
$pageContent = $pageArray['revisions'][0]['*'];

$showcaseItems = substr_count( $pageContent, '{{ShowcaseItem|' );

WikimediaGraphite::sendNow( 'daily.wikidata.showcaseItems', $showcaseItems );
WikimediaStatsdExporter::sendNow( 'daily_wikidata_showcaseItems_total', $showcaseItems );

$output->markEnd();
