#!/usr/bin/php
<?php
/**
 * @author Addshore
 * Track Wikidata usage on clients in graphite
 * Used by: https://grafana.wikimedia.org/d/000000160/wikidata-entity-usage
 *          https://grafana.wikimedia.org/d/000000176/wikidata-entity-usage-project
 *
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-entityUsage' )->markStart();

$dblist = WikimediaCurl::curlGetExternal(
	'https://noc.wikimedia.org/conf/dblists/wikidataclient.dblist'
);
if ( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for EntityUsage tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$sectionMapper = new WikimediaDbSectionMapper();
$date = date( DATE_ATOM );
$perAspectValues = [];
foreach ( $dbs as $dbname ) {
	$pdo = WikimediaDb::getPdoNewHosts( $dbname, $sectionMapper );
	// Count each type of entity usage
	$sql = "SELECT eu_aspect as aspect, count(*) as count FROM $dbname.wbc_entity_usage GROUP BY eu_aspect";
	$queryResult = $pdo->query( $sql );
	$perSiteValue = 0;
	$perSiteMetricName = 'daily.wikidata.entity_usage_per_site.' . $dbname;
	if ( $queryResult === false ) {
		$output->outputMessage( "EntityUsage DB query failed for $dbname, Skipping!!" );
	} else {
		foreach ( $queryResult as $row ) {
			$value = $row['count'];
			$aspectWithModifier = explode( '.', $row['aspect'] );
			$aspect = $aspectWithModifier[0];
			$modifierSuffix = isset( $aspectWithModifier[1] ) ? '_' . $aspectWithModifier[1] : '';
			$metricName = 'daily.wikidata.entity_usage.' . $dbname . '.' . $aspect . $modifierSuffix;
			WikimediaGraphite::send( $metricName, $value, $date );
			WikimediaStatsdExporter::sendNow(
				'daily_wikidata_entityUsage_total',
				$value,
				[ 'site_id' => $dbname, 'aspect' => $aspect, 'modifier' => $modifierSuffix ]
			);
			$perSiteValue += (int)$value;
			$perAspectValues[$aspect] = ( $perAspectValues[$aspect] ?? 0 ) + (int)$value;
		}
		WikimediaGraphite::send( $perSiteMetricName, $perSiteValue, $date );
	}

	// Count usage (excluding sitelinks) on distinct pages
	$sql = "SELECT COUNT(DISTINCT eu_page_id) AS pages FROM $dbname.wbc_entity_usage WHERE eu_aspect != 'S'";
	$queryResult = $pdo->query( $sql );
	if ( $queryResult === false ) {
		$output->outputMessage( "EntityUsage page DB query failed for $dbname, Skipping!!" );
	} else {
		$queryResult = $queryResult->fetchAll();
		$metricName = 'daily.wikidata.entity_usage_pages.' . $dbname;
		$value = $queryResult[0]['pages'];
		WikimediaGraphite::send( $metricName, $value, $date );
		WikimediaStatsdExporter::sendNow(
			'daily_wikidata_entityUsagePages_total',
			$value,
			[ 'site_id' => $dbname ]
		);
	}

}

foreach ( $perAspectValues as $aspect => $value ) {
	$perAspectMetricName = 'daily.wikidata.entity_usage_per_aspect.' . $aspect;
	WikimediaGraphite::send( $perAspectMetricName, $value, $date );
	WikimediaStatsdExporter::sendNow(
		'daily_wikidata_entityUsagePerAspect_total',
		$value,
		[ 'aspect' => $aspect ]
	);
}
$output->markEnd();
