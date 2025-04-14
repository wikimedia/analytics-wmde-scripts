#!/usr/bin/php
<?php
/**
 * Track Wikidata table auto increment values and compare to max value
 *
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-autoincrementRatio' )->markStart();

$sectionMapper = new WikimediaDbSectionMapper();
$pdo = WikimediaDb::getPdoNewHosts( 'wikidatawiki', $sectionMapper );
// Count each type of entity usage
$sql = "SELECT `TABLE_NAME`, `AUTO_INCREMENT` FROM `information_schema`.`TABLES` WHERE `TABLE_SCHEMA` = 'wikidatawiki' AND `AUTO_INCREMENT` is not NULL;";
$queryResult = $pdo->query( $sql );
foreach ( $queryResult as $row ) {
	$tableName = $row['TABLE_NAME'];
	$autoIncrementValue = (int)$row['AUTO_INCREMENT'];
	// TODO: Get datatypes from abstract schema
	$createTable = $pdo->query( "SHOW CREATE TABLE wikidatawiki.$tableName;" );
	if ( $createTable === false || !$createTable ) {
		$output->outputMessage( "DB query failed for $tableName, Skipping!!" );
		continue;
	}
	$createTable = $createTable->fetch()['Create Table'];
	$autoIncrementDefRow = [];
	preg_match( '/(.+?)AUTO_INCREMENT/', $createTable, $autoIncrementDefRow );
	if ( !$autoIncrementDefRow ) {
		$output->outputMessage( "Could not find auto increment column for $tableName, Skipping!!" );
		continue;
	}
	$autoIncrementDefRow = strtolower( $autoIncrementDefRow[0] );
	if ( strpos( $autoIncrementDefRow, 'tinyint' ) !== false ) {
		$maxAutoIncrementValue = 127;
	} elseif ( strpos( $autoIncrementDefRow, 'smallint' ) !== false ) {
		$maxAutoIncrementValue = 32767;
	} elseif ( strpos( $autoIncrementDefRow, 'mediumint' ) !== false ) {
		$maxAutoIncrementValue = 8388607;
	} elseif ( strpos( $autoIncrementDefRow, 'bigint' ) !== false ) {
		$maxAutoIncrementValue = 9223372036854775807;
	} elseif ( strpos( $autoIncrementDefRow, 'int' ) !== false ) {
		$maxAutoIncrementValue = 2147483647;
	} else {
		$output->outputMessage( "Could not understand auto increment column for $tableName, Skipping!!" );
		continue;
	}

	if ( strpos( $autoIncrementDefRow, 'unsigned' ) !== false ) {
		$maxAutoIncrementValue *= 2;
	}

	$ratio = $autoIncrementValue / $maxAutoIncrementValue;
	$percent = ( $autoIncrementValue * 100 ) / $maxAutoIncrementValue;
	// Ignore below 1%
	if ( $percent < 1 ) {
		continue;
	}

	$metricName = 'daily.wikidata.reliability_metrics.auto_increment_ratio.' . $tableName;
	WikimediaGraphite::sendNow( $metricName, $percent );
	WikimediaStatsdExporter::sendNow( 'daily_wikidata_reliability_metrics_auto_increment_ratio', $ratio, [ 'table' => $tableName ] );
}

$output->markEnd();
