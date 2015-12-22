#!/usr/bin/php
<?php

$dateYesterday = new DateTime();
$dateYesterday->modify( '-24 hours' );

$query = file_get_contents( __DIR__ . '/hql/entitydata.hql' );
$query = str_replace( '<<YEAR>>', date( 'Y', $dateYesterday->getTimestamp() ), $query );
$query = str_replace( '<<MONTH>>', date( 'n', $dateYesterday->getTimestamp() ), $query );
$query = str_replace( '<<DAY>>', date( 'j', $dateYesterday->getTimestamp() ), $query );

$outputFile = __DIR__ . '/entitydata_result.txt';
$errorFile = __DIR__ . '/entitydata_errors.txt';

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
	exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
}
