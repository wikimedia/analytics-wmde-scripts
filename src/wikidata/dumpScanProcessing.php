#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../lib/load.php' );

$config = Config::getConfig();
$dataDir = $config['dump-dir'];
//Make sure the output dir exists
if ( !file_exists( $dataDir ) ) {
	throw new Exception( "Data directory does not exist: " . $dataDir );
}

//Get all the output directories
$dirs = array_filter( glob( $dataDir . '/*' ), 'is_dir' );
sort( $dirs );
$dirs = array_reverse( $dirs );

//Only get the last 10 dumps
$dirs = array_slice( $dirs, 0, 10 );
if( count( $dirs ) <= 1 ) {
	echo "Not many output dirs found!\n";
}

//Get the outputs and send to graphite
foreach( $dirs as $dir ) {
	$dir = rtrim( $dir, '\/' );
	$dirParts = explode( '/', $dir );
	$date = array_pop( $dirParts );

	$file = $dir . '/metrics.json';
	if( !file_exists( $file ) ) {
		echo 'File not found: ' . $file . "\n";
		continue;
	}

	$data = json_decode( file_get_contents( $file ), true );
	foreach( $data as $name => $value ) {
		$metricName = "daily.wikidata.datamodel.$name";
		WikimediaGraphite::send( $metricName, $value, $date );
	}

}