#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Track Wikidata dispatch lag in graphite
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-dispatch
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-dispatch' )->markStart();

$url = 'https://www.wikidata.org/w/api.php?action=query&meta=siteinfo&format=json&siprop=statistics';
$json = WikimediaCurl::curlGetExternal( $url );

if ( $json === false ) {
	throw new RuntimeException( 'Failed to get dispatch lag from API' );
}

$json = json_decode( $json[1], true );
if ( !array_key_exists( 'dispatch', $json['query']['statistics'] ) ) {
	throw new RuntimeException( 'No dispatch stats in API response' );
}
$json = $json['query']['statistics']['dispatch'];

$stats = [];
$stats['freshest.pending'] = $json['freshest']['pending'];
$stats['freshest.lag'] = $json['freshest']['lag'];
$stats['median.pending'] = $json['median']['pending'];
$stats['median.lag'] = $json['median']['lag'];
$stats['stalest.pending'] = $json['stalest']['pending'];
$stats['stalest.lag'] = $json['stalest']['lag'];
$stats['average.pending'] = $json['average']['pending'];
$stats['average.lag'] = $json['average']['lag'];

foreach ( $stats as $name => $value ) {
	// Send the data directly to graphite rather than it getting stuck in a statsd bucket for 1 minute.
	// This is fine as this script runs once per minute.
	WikimediaGraphite::sendNow( "wikidata.dispatch.$name", $value );
}

$output->markEnd();
