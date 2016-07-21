#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends data about the number of times Special:EntityData is accessed on wikidata.org and by
 * what types of user agents and by what formats.
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-special-entitydata
 */

require_once( __DIR__ . '/../../lib/load.php' );
Output::startScript( __FILE__ );

if ( array_key_exists( 1, $argv ) ) {
	$daysAgo = $argv[1];
	Output::timestampedMessage( "Getting data from $daysAgo days ago" );
} else {
	$daysAgo = 1;
}

$dateYesterday = new DateTime();
$dateYesterday->modify( '-' . $daysAgo . ' day' );

$query = file_get_contents( __DIR__ . '/hql/specialentitydata.hql' );
$query = str_replace( '<<YEAR>>', date( 'Y', $dateYesterday->getTimestamp() ), $query );
$query = str_replace( '<<MONTH>>', date( 'n', $dateYesterday->getTimestamp() ), $query );
$query = str_replace( '<<DAY>>', date( 'j', $dateYesterday->getTimestamp() ), $query );

$outputFile = __DIR__ . "/entitydata_result_$daysAgo.txt";
$errorFile = __DIR__ . "/entitydata_errors_$daysAgo.txt";

// -S is silent (to avoid printing query progress to stdout)
shell_exec( "hive -S -e \"$query\" > $outputFile 2> $errorFile" );

//TODO look at $errorFile and print errors? Excluding the line below?
//log4j:WARN No such property [maxBackupIndex] in org.apache.log4j.DailyRollingFileAppender.

$result = file_get_contents( $outputFile );
$resultLines = explode( "\n", $result );

$metrics = array();
foreach( $resultLines as $lineNumber => $line ) {
	if( $lineNumber === 0 ) {
		continue;
	}

	// Split the line
	@list( $count, $agentType, $contentType ) = explode( "\t", $line );

	// Normalize content type
	if( strstr( $contentType, '/rdf+xml' ) ) {
		$format = 'rdf';
	} elseif( strstr( $contentType, '/vnd.php' ) ) {
		$format = 'php';
	} elseif( strstr( $contentType, '/n-triples' ) ) {
		$format = 'nt';
	} elseif( strstr( $contentType, '/n3' ) ) {
		$format = 'n3';
	} elseif( strstr( $contentType, '/json' ) ) {
		$format = 'json';
	} elseif( strstr( $contentType, '/turtle' ) ) {
		$format = 'ttl';
	} elseif( strstr( $contentType, '/html' ) ) {
		$format = 'html';
	} else {
		$format = 'unknown';
	}

	if( $count > 0 ) {
		@$metrics["daily.wikidata.entitydata.format.$format"] += $count;
		@$metrics["daily.wikidata.entitydata.agent_types.$agentType"] += $count;
	}
}

foreach( $metrics as $metricName => $value ) {
	$targetDate = $dateYesterday->format( 'Y-m-d' );
	WikimediaGraphite::send( $metricName, $value, $targetDate );
}

unlink( $outputFile );
