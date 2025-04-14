#!/usr/bin/php
<?php
/**
 * Track Wikidata dispatch metrics from wb_changes table
 * Used by: https://grafana.wikimedia.org/d/hGFN2TH7z/edit-dispatching-via-jobs
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-wb_changes' )->markStart();

$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
$queryResult = $pdo->query( 'SELECT MAX(change_time) AS max, MIN(change_time) AS min, COUNT(*) AS changes FROM wikidatawiki.wb_changes;' );

if ( $queryResult === false ) {
	throw new RuntimeException( 'Something went wrong with the db query' );
}

$rows = $queryResult->fetchAll();
if ( !$rows ) {
	throw new RuntimeException( 'Something went wrong with the db query' );
}

$numberOfChanges = $rows[ 0 ][ 'changes' ];
WikimediaGraphite::sendNow( 'wikidata.dispatch_job.wb_changes.number_of_rows', $numberOfChanges );
WikimediaStatsdExporter::sendNow( 'wikidata_dispatch_job_wb_changes_number_of_rows_total', $numberOfChanges );
if ( $numberOfChanges > 0 ) {
	$max = DateTime::createFromFormat( 'YmdHis', $rows[ 0 ][ 'max' ] );
	$min = DateTime::createFromFormat( 'YmdHis', $rows[ 0 ][ 'min' ] );
	$now = new DateTime();
	WikimediaGraphite::sendNow( 'wikidata.dispatch_job.wb_changes.freshest', $now->getTimestamp() - $max->getTimestamp() );
	WikimediaGraphite::sendNow( 'wikidata.dispatch_job.wb_changes.stalest', $now->getTimestamp() - $min->getTimestamp() );
	WikimediaStatsdExporter::sendNow( 'wikidata_dispatch_job_wb_changes_freshest_timestamp_seconds', $max->getTimestamp() );
	WikimediaStatsdExporter::sendNow( 'wikidata_dispatch_job_wb_changes_stalest_timestamp_seconds', $min->getTimestamp() );
}
$output->markEnd();
