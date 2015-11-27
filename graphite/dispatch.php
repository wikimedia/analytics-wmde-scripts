#!/usr/bin/php
<?php
/**
 * Track Wikidata dispatch lag in graphite
 *
 * @author Addshore
 */

$opts = array(
	'http' => array(
		'method' => "GET",
		'header' => "User-Agent: WMDE Wikidata metrics gathering\r\n",
	),
);

$context = stream_context_create( $opts );

$json =
	file_get_contents(
		'https://www.wikidata.org/w/api.php?action=query&meta=siteinfo&format=json&siprop=statistics',
		false,
		$context
	);

if( $json === false ) {
	throw new RuntimeException( "Failed to get dispatch lag from API" );
}

$json = json_decode( $json, true );
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
