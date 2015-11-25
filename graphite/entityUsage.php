<?php
/**
 * Track Wikidata usage on clients in graphite
 *
 * @author Addshore
 */

require_once( __DIR__ . '/../src/WikimediaDb.php' );

$dblist = file_get_contents( 'https://noc.wikimedia.org/conf/wikidataclient.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for EntityUsage tracking!' );
}
$dbs = explode( "\n", $dblist );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

foreach( $dbs as $dbname ) {
	$sql = "SELECT eu_aspect as aspect, count(*) as count FROM $dbname.wbc_entity_usage GROUP BY eu_aspect";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		echo "EntityUsage DB query failed for $dbname, Retrying!\n";
		$queryResult = $pdo->query( $sql );
		if( $queryResult === false ) {
			echo "EntityUsage DB query failed for $dbname, Skipping!!\n";
			continue;
		}
	}
	foreach( $queryResult as $row ) {
		$value = $row['count'];
		$metricName = 'daily.wikidata.entity_usage.' . $dbname . '.' . str_replace( '.', '_', $row['aspect'] );
		exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}
}
