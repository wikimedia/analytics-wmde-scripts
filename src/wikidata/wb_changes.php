#!/usr/bin/php
<?php
/**
 * Track Wikidata dispatch metrics from wb_changes table
 * Used by: https://grafana.wikimedia.org/d/hGFN2TH7z/edit-dispatching-via-jobs
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-wb_changes' )->markStart();

$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
$queryResult = $pdo->query( 'SELECT MAX(change_time) AS max, MIN(change_time) AS min FROM wikidatawiki.wb_changes;' );

if ( $queryResult === false ) {
	throw new RuntimeException( 'Something went wrong with the db query' );
}

$rows = $queryResult->fetchAll();
if ( !$rows ) {
	throw new RuntimeException( 'Something went wrong with the db query' );
}

$max = DateTime::createFromFormat( 'YmdHis', $rows[0]['max'] );
$min = DateTime::createFromFormat( 'YmdHis', $rows[0]['min'] );
$now = new DateTime();
WikimediaGraphite::sendNow( 'wikidata.dispatch_job.wb_changes.freshest', $now->getTimestamp() - $max->getTimestamp() );
WikimediaGraphite::sendNow( 'wikidata.dispatch_job.wb_changes.stalest', $now->getTimestamp() - $min->getTimestamp() );
$output->markEnd();
