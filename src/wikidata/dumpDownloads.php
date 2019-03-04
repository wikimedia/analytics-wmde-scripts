#!/usr/bin/php
<?php
/**
 * @author Addshore
 * Track the downloads of Wikidata dumps
 * https://phabricator.wikimedia.org/T119070
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-dumpDownloads' )->markStart();

$logDirectory = Config::getValue( 'dump_log_dir' );

// Types suffixed with a 1 represent the first part of a joined dump (only count the first)
$weeklyXmlTypes = [
	'pages-articles-multistream',
	'pages-meta-history',
	'pages-meta-current',
	'pages-articles',
];

$regexSnips = [
	// latest-all.json.EXT
	// wikidata-20160101-all.json.EXT
	'full.json' =>
		'(latest|wikidata-[0-9]{8})-all\.json\.(gz|bz2)',
	// latest-all-BETA.ttl.EXT
	// wikidata-20160101-all-BETA.ttl.EXT
	'full.ttl_beta' =>
		'(latest|wikidata-[0-9]{8})-all-BETA\.ttl\.(gz|bz2)',
	// wikidatawiki-20160701-pages-articles-multistream.xml.bz2
	// wikidatawiki-20160701-pages-meta-history.xml.bz2
	// wikidatawiki-20160701-pages-meta-history1.xml-p000000001p000022835.7z
	// wikidatawiki-20160701-pages-meta-current.xml.bz2
	// wikidatawiki-20160720-pages-meta-current1.xml-p000000001p002421529.bz2
	// wikidatawiki-20160720-pages-articles.xml.bz2
	// wikidatawiki-20160720-pages-articles1.xml-p000000001p002421529.bz2
	'full.xml' =>
		// Note the '1?' in this regex means the first part of split dumps is also matched!
		'wikidatawiki-(latest|[0-9]{8})-(' . implode( '|', $weeklyXmlTypes ) . ')1?\.xml\.(gz|bz2)',
	// wikidatawiki-20160626-pages-meta-hist-incr.xml.EXT
	'incr.xml' =>
		'wikidatawiki-[0-9]{8}-pages-meta-hist-incr.xml\.(gz|bz2)',
];

// Optionally take a date passed into the script
if ( array_key_exists( 1, $argv ) ) {
	$targetTime = strtotime( $argv[1] );
} else {
	$targetTime = strtotime( '-4 days', time() );
}

$targetDate = date( 'd/M/Y', $targetTime );// For format [01/Jul/2016:
$graphiteDate = date( 'Y-m-d', $targetTime );// Date formatted for graphite
$fileFormatDayAfter = date( 'Ymd', strtotime( '+1 days', $targetTime ) );
$fileFormatTargetDate = date( 'Ymd', $targetTime );

$logFiles = [
	$logDirectory . DIRECTORY_SEPARATOR . 'access.log-' . $fileFormatDayAfter . '.gz',
	$logDirectory . DIRECTORY_SEPARATOR . 'access.log-' . $fileFormatTargetDate . '.gz',
];

$output->outputMessage( 'Targeting date: ' . $targetDate );

$counters = [];
foreach ( $logFiles as $logFile ) {
	$handle = fopen( 'compress.zlib://' . $logFile, 'r' );
	if ( $handle === false ) {
		throw new RuntimeException( 'Failed to open file: ' . $logFile );
	}

	while ( ( $line = fgets( $handle ) ) !== false ) {
		if (
			// Log line should contain out target date
			strpos( $line, "[$targetDate:" ) === false ||
			// And contain wikidatawiki in the request URI
			strpos( $line, '/wikidatawiki/' ) === false
		) {
			continue;
		}

		$statusCode = 0;
		if ( strpos( $line, ' 200 ' ) ) {
			$statusCode = 200;
		} elseif ( strpos( $line, ' 206 ' ) ) {
			$statusCode = 206;
		} else {
			// Only count 200 or 206 status codes
			continue;
		}

		foreach ( $regexSnips as $type => $regexSnip ) {
			if ( preg_match( "/$regexSnip/i", $line ) ) {
				@$counters["$type.$statusCode"]++;
				// Once we have matched 1 type of dump do to the next line.
				break;
			}
		}

	}
	fclose( $handle );
}

// Send everything to graphite!
foreach ( $counters as $type => $value ) {
	$metricName = 'daily.wikidata.dump_requests.' . $type;
	WikimediaGraphite::send( $metricName, $value, $graphiteDate );
}

$output->markEnd();
