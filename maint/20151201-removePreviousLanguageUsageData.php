#!/usr/bin/php
<?php
/**
 * @author Addshore
 *
 * As of 863acb9c0f1d7da54c64ae9908a80671bb79edfd language data should only be tracked
 * for users that are classed as active.
 * Data that has thus been tracked prior to this massively distorts the display of this data
 * and thus should be removed.
 * Graphite does not support setting a value back to NULL so we will take data from today (20151201)
 * and back fill the dates where we already have data.
 */

require_once __DIR__ . '/../lib/load.php';

// This URL holds the data with which to back fill
$dataUrl = 'https://graphite.wikimedia.org/render/?target=daily.wikidata.site_stats.language_usage.*&from=20151130&until=20151201&format=json';
$json = WikimediaCurl::curlGetExternal( $dataUrl );
$data = json_decode( $json[1], true );

// Dates to be back filled
$dates = [
	'2015-11-24',
	'2015-11-25',
	'2015-11-26',
	'2015-11-27',
	'2015-11-28',
	'2015-11-29',
	'2015-11-30',
];

// Fill the data for all langs and dates
foreach ( $dates as $date ) {
	foreach ( $data as $metric ) {
		sendMetric( $metric['target'], $metric['datapoints'][0][0], $date );
	}
}

/**
 * @param string $name name of the metric
 * @param string|int $value value to set
 * @param string $date YYY-mm-dd
 */
function sendMetric( $name, $value, $date ) {
	exec(
		"echo \"$name $value `date -d \"$date\" +%s`\" | nc -q0 graphite.eqiad.wmnet 2003"
	);
}
