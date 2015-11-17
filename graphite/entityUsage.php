<?php
/**
 * Track Wikidata usage on clients in graphite
 *
 * @author Addshore
 */

$dblist = file_get_contents( 'https://noc.wikimedia.org/conf/wikidataclient.dblist' );
if( $dblist === false ) {
	die( "Failed to get db list!" );
}
$dbs = explode( "\n", $dblist );
$dbs = array_filter( $dbs );

$sqlConf = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );

foreach( $dbs as $dbname ) {
	$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet", $sqlConf['user'], $sqlConf['password'] );
	$sql = "SELECT eu_aspect as aspect, count(*) as count FROM $dbname.wbc_entity_usage GROUP BY eu_aspect";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		echo "DB query failed for $dbname, Retrying!\n";
		$queryResult = $pdo->query( $sql );
		if( $queryResult === false ) {
			echo "DB query failed for $dbname, Skipping!!\n";
			continue;
		}
	}
	foreach( $queryResult as $row ) {
		$value = $row['count'];
		$metricName = 'daily.wikidata.entity_usage.' . $dbname . '.' . str_replace( '.', '_', $row['aspect'] );
		exec( "echo \"$metricName $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}
}
