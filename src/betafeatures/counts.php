#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about betafeatures usage to graphite
 * Used by: https://grafana.wikimedia.org/dashboard/db/mediawiki-betafeatures
 */

/**
 * To update this list see wgBetaFeaturesWhitelist in
 * https://noc.wikimedia.org/conf/InitialiseSettings.php.txt
 */
$currentFeatures = array(
	'visualeditor-enable',
	'beta-feature-flow-user-talk-page',
	'uls-compact-links',
	'popups',
	'cx',
	'read-more',
	'cirrussearch-completionsuggester',
	'ores-enabled',
	'revisionslider',
);

require_once( __DIR__ . '/../../lib/load.php' );

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/all.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for beta feature tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

$metrics = array();

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' ) {
		continue;
	}
	// Count each type of entity usage
	$sql = "SELECT * FROM betafeatures_user_counts";
	$queryResult = $pdo->query( $sql );

	if( $queryResult === false ) {
		echo "beta features DB query failed for $dbname, Skipping!!\n";
	} else {

		foreach( $queryResult as $row ) {
			$feature = $row['feature'];
			$number = $row['number'];
			@$metrics[$feature] += $number;
		}

	}
}

foreach( $metrics as $featureName => $value ) {
	if ( in_array( $featureName, $currentFeatures ) && $value > 0 ) {
		WikimediaGraphite::sendNow( 'daily.betafeatures.user_counts.totals.' . $featureName, $value );
	}
}
