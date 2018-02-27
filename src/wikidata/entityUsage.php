#!/usr/bin/php
<?php
/**
 * @author Addshore
 * Track Wikidata usage on clients in graphite
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-entity-usage
 *          https://grafana.wikimedia.org/dashboard/db/wikidata-entity-usage-project
 *
 */

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'wikidata-entityUsage' )->markStart();

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/dblists/wikidataclient.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for EntityUsage tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

foreach( $dbs as $dbname ) {
	// Count each type of entity usage
	$sql = "SELECT eu_aspect as aspect, count(*) as count FROM $dbname.wbc_entity_usage GROUP BY eu_aspect";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		$output->outputMessage( "EntityUsage DB query failed for $dbname, Skipping!!" );
	} else {
		foreach( $queryResult as $row ) {
			$value = $row['count'];
			$metricName = 'daily.wikidata.entity_usage.' . $dbname . '.' . str_replace( '.', '_', $row['aspect'] );
			WikimediaGraphite::sendNow( $metricName, $value );
		}
	}

	// Count usage (excluding sitelinks) on distinct pages
	$sql = "SELECT COUNT(DISTINCT eu_page_id) AS pages FROM $dbname.wbc_entity_usage WHERE eu_aspect != 'S'";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		$output->outputMessage( "EntityUsage page DB query failed for $dbname, Skipping!!" );
	} else {
		$queryResult = $queryResult->fetchAll();
		$metricName = 'daily.wikidata.entity_usage_pages.' . $dbname;
		$value = $queryResult[0]['pages'];
		WikimediaGraphite::sendNow( $metricName, $value );
	}

}

$output->markEnd();
