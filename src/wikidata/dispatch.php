#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Track Wikidata dispatch lag in graphite
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-dispatch
 */

require_once( __DIR__ . '/../../lib/load.php' );

$url = 'https://www.wikidata.org/w/api.php?action=query&meta=siteinfo&format=json&siprop=statistics';
$json = WikimediaCurl::curlGet( $url );

if( $json === false ) {
	throw new RuntimeException( "Failed to get dispatch lag from API" );
}

$json = json_decode( $json[1], true );
$json = $json['query']['statistics']['dispatch'];

$stats = array();
$stats['freshest.pending'] = $json['freshest']['pending'];
$stats['freshest.lag'] = $json['freshest']['lag'];
$stats['median.pending'] = $json['median']['pending'];
$stats['median.lag'] = $json['median']['lag'];
$stats['stalest.pending'] = $json['stalest']['pending'];
$stats['stalest.lag'] = $json['stalest']['lag'];
$stats['average.pending'] = $json['average']['pending'];
$stats['average.lag'] = $json['average']['lag'];

foreach ( $stats as $name => $value ) {
	exec( "echo \"wikidata.dispatch.$name:$value|g\" | nc -w 1 -u statsd.eqiad.wmnet 8125" );
}
