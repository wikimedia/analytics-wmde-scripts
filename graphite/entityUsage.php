#!/usr/bin/php
<?php
/**
 * Track Wikidata usage on clients in graphite
 *
 * @author Addshore
 */

require_once( __DIR__ . '/../src/WikimediaDb.php' );
require_once( __DIR__ . '/../src/WikimediaCurl.php' );

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/wikidataclient.dblist' );
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
		echo "EntityUsage DB query failed for $dbname, Skipping!!\n";
	} else {
		foreach( $queryResult as $row ) {
			$value = $row['count'];
			$metricName = 'daily.wikidata.entity_usage.' . $dbname . '.' . str_replace( '.', '_', $row['aspect'] );
			exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
		}
	}

	// Count usage (excluding sitelinks) on distinct pages
	$sql = "SELECT COUNT(DISTINCT eu_page_id) AS pages FROM $dbname.wbc_entity_usage WHERE eu_aspect != 'S'";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		echo "EntityUsage page DB query failed for $dbname, Skipping!!\n";
	} else {
		$queryResult = $queryResult->fetchAll();
		$metricName = 'daily.wikidata.entity_usage_pages.' . $dbname;
		$value = $queryResult[0]['pages'];
		exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
