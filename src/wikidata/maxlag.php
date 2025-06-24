#!/usr/bin/php
<?php
/**
 *
 * @author Jonas Kress
 * Track Wikidata max lag
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-dispatch
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-maxlag' )->markStart();

// produce maxlag error to get maxlag from error description
$url = 'https://www.wikidata.org/w/api.php?action=query&titles=MediaWiki&format=json&maxlag=-1';
$json = WikimediaCurl::curlGetExternal( $url );
$json = json_decode( $json[1], true );

if ( $json['error']['code'] !== 'maxlag' ) {
	throw new RuntimeException( 'Failed to get max lag from API' );
}

$lag = $json['error']['lag'];
$type = $json['error']['type'];

WikimediaStatsdExporter::sendNow( 'wikidata_maxlag_seconds', $lag, [ 'type' => $type ] );
$output->markEnd();
