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
Output::startScript( __FILE__ );

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/all.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for beta feature tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

$metrics = array();

// Create temporary tables
$sql = "CREATE TEMPORARY TABLE IF NOT EXISTS staging.wmde_analytics_betafeature_users";
$sql .= "( user_name VARCHAR(255) NOT NULL, feature VARBINARY(255) NOT NULL, PRIMARY KEY (user_name, feature) )";
$queryResult = $pdo->query( $sql );
if ( $queryResult === false ) {
	die( "Failed to create staging.wmde_analytics_betafeature_users" );
}

foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}
	// Aggregate the overall betafeatures_user_counts
	$sql = "SELECT * FROM $dbname.betafeatures_user_counts";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		Output::timestampedMessage( "beta features DB query 1 failed for $dbname, Skipping!! " );
	} else {
		foreach( $queryResult as $row ) {
			$feature = $row['feature'];
			$number = $row['number'];
			@$metrics[$feature] += $number;
		}
	}

	// Record individuals
	foreach( $currentFeatures as $feature ) {
		$sql = "INSERT IGNORE INTO staging.wmde_analytics_betafeature_users ( user_name, feature )";
		$sql .= " SELECT user_name, up_property FROM $dbname.user_properties";
		$sql .= " JOIN $dbname.user ON up_user = user_id";
		$sql .= " WHERE up_property = '$feature' AND up_value = '1'";
		$queryResult = $pdo->query( $sql );
		if( $queryResult === false ) {
			Output::timestampedMessage( "beta features DB query 2 failed for $dbname for feature $feature, Skipping!!" );
		}
	}
}

foreach( $metrics as $featureName => $value ) {
	if ( in_array( $featureName, $currentFeatures ) && $value > 0 ) {
		WikimediaGraphite::sendNow( 'daily.betafeatures.user_counts.totals.' . $featureName, $value );
	}
}

$sql = "SELECT COUNT(*) AS count, feature";
$sql .= " FROM staging.wmde_analytics_betafeature_users";
$sql .= " GROUP BY up_property";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	Output::timestampedMessage( "beta features select from staging.wmde_analytics_betafeature_users failed!!" );
} else {
	foreach( $queryResult as $row ) {
		$feature = $row['feature'];
		$count = $row['count'];
		WikimediaGraphite::sendNow( 'daily.betafeatures.global_user_counts.totals.' . $featureName, $value );
	}
}