#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../src/WikimediaCurl.php' );

$url = 'https://www.wikidata.org/w/api.php?action=query&prop=revisions&titles=Wikidata:Showcase_items&rvprop=content';
$json = WikimediaCurl::curlGet( $url );

if( $json === false ) {
	throw new RuntimeException( "Failed to get showcase items from API" );
}

$json = json_decode( $json[1], true );
$pageArray = array_pop( $json['query']['pages'] );
$pageContent = $pageArray['revisions'][0]['*'];

$showcaseItems = substr_count( $pageContent, '{{ShowcaseItem|' );

exec( "echo \"daily.wikidata.showcaseItems $showcaseItems `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
