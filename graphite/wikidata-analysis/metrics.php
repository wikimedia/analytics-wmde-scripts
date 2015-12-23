#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This makes an assumption that the data directory is ~/wikidata-analysis/data
 * See https://github.com/wmde/wikidata-analysis
 */

//TODO this will not work on windows (but that is not our target)
//http://stackoverflow.com/questions/1894917/how-to-get-the-home-directory-from-a-php-cli-script
$dataDir = $_SERVER['HOME'] . '/wikidata-analysis/data/';
//Make sure the output dir exists
if ( !file_exists( $dataDir ) ) {
	throw new Exception( "Data directory does not exist" );
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
		exec( "echo \"$metricName $value `date -d \"$date\" +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
