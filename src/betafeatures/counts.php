#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about betafeatures usage to graphite
 * Used by: https://grafana.wikimedia.org/dashboard/db/betafeatures
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
	'visualeditor-newwikitext',
	'twocolconflict',
	'rcenhancedfilters',
	'codemirror-syntax-highlight',
	'advancedsearch',
	'visualeditor-visualdiffpage',
);

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'betafeature-counts' )->markStart();

$dbs = WikimediaDbList::get( 'all' );

$pdo = WikimediaDb::getPdo();

$metrics = array();
$todaysTableName = 'staging.wmde_analytics_betafeature_users_today';
$yesterdayTableName = 'staging.wmde_analytics_betafeature_users_yesterday';

// Create todays table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS $todaysTableName";
$sql .= "( user_name VARCHAR(255) NOT NULL, feature VARBINARY(255) NOT NULL, PRIMARY KEY (user_name, feature) )";
$queryResult = $pdo->query( $sql );
if ( $queryResult === false ) {
	die( "Failed to create table $todaysTableName" );
}
// Create yesterday table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS $yesterdayTableName";
$sql .= "( user_name VARCHAR(255) NOT NULL, feature VARBINARY(255) NOT NULL, PRIMARY KEY (user_name, feature) )";
$queryResult = $pdo->query( $sql );
if ( $queryResult === false ) {
	die( "Failed to create table $yesterdayTableName" );
}

// Clear todays table (if it for some reason has data in it)
$sql = "TRUNCATE TABLE $todaysTableName";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "FAILED: $sql" );
}

// Loop through all wiki databases
foreach( $dbs as $dbname ) {
	if( $dbname === 'labswiki' || $dbname === 'labtestwiki' ) {
		continue;
	}
	// Aggregate the overall betafeatures_user_counts
	$sql = "SELECT * FROM $dbname.betafeatures_user_counts";
	$queryResult = $pdo->query( $sql );
	if( $queryResult === false ) {
		$output->outputMessage( "SELECT 1 failed for $dbname, Skipping!! " );
	} else {
		foreach( $queryResult as $row ) {
			$feature = $row['feature'];
			$number = $row['number'];
			@$metrics[$feature] += $number;
		}
	}

	// Record individuals into the temp table
	foreach( $currentFeatures as $feature ) {
		$sql = "INSERT IGNORE INTO $todaysTableName ( user_name, feature )";
		$sql .= " SELECT user_name, up_property FROM $dbname.user_properties";
		$sql .= " JOIN $dbname.user ON up_user = user_id";
		$sql .= " WHERE up_property = '$feature' AND up_value = '1'";
		$queryResult = $pdo->query( $sql );
		if( $queryResult === false ) {
			$output->outputMessage( "INSERT INTO FAILED for $dbname for feature $feature, Skipping!!" );
		}
	}
}

// Send total user_counts (1 global user can be counted more than once)
foreach( $metrics as $featureName => $value ) {
	if ( in_array( $featureName, $currentFeatures ) && $value > 0 ) {
		WikimediaGraphite::sendNow( 'daily.betafeatures.user_counts.totals.' . $featureName, $value );
	}
}

// Select and send the global user counts (each global user is only counted once)
$sql = "SELECT COUNT(*) AS count, feature";
$sql .= " FROM $todaysTableName";
$sql .= " GROUP BY feature";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "SELECT FROM temp table $todaysTableName FAILED!!" );
} else {
	foreach( $queryResult as $row ) {
		if ( in_array( $row['feature'], $currentFeatures ) && $row['count'] > 0 ) {
		WikimediaGraphite::sendNow(
			'daily.betafeatures.global_user_counts.totals.' . $row['feature'],
			$row['count']
		);
		}
	}
}

// Compare todays data with yesterdays data (if present)
$queryResult = $pdo->query( "SELECT * FROM $yesterdayTableName LIMIT 1" );
if ( $queryResult === false ) {
	$output->outputMessage( "FAILED: $sql" );
} else if( count( $queryResult->fetchAll() ) > 0 ) {
	// Work out what has changed between days
	// Emulated INTERSECT: http://stackoverflow.com/a/950505/4746236
	$sql = "SELECT 'enables' AS state, today.* FROM $todaysTableName AS today";
	$sql .= " WHERE ROW(today.user_name, today.feature) NOT IN";
	$sql .= " ( SELECT * FROM $yesterdayTableName )";
	$sql .= " UNION ALL";
	$sql .= " SELECT 'disables' AS state, yesterday.* FROM $yesterdayTableName AS yesterday";
	$sql .= " WHERE ROW(yesterday.user_name, yesterday.feature) NOT IN";
	$sql .= " ( SELECT * FROM $todaysTableName )";
	$sql = "SELECT state, COUNT(*) AS count, feature FROM ( $sql ) AS a GROUP BY state, feature";
	$queryResult = $pdo->query( $sql );
	if ( $queryResult === false ) {
		$output->outputMessage( "FAILED Intersection, Skipping!!" );
	} else {
		foreach( $queryResult as $row ) {
			WikimediaGraphite::sendNow(
				'daily.betafeatures.global_' . $row['state'] . '.totals.' . $row['feature'],
				$row['count']
			);
		}
	}
} else {
	$output->outputMessage( "No data contained in yesterdays table, Skipping!!" );
}

// Clear yesterdays table
$sql = "TRUNCATE TABLE $yesterdayTableName";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "FAILED: $sql" );
}

// Add todays data into the yesterday table
$sql = "INSERT INTO $yesterdayTableName ( user_name, feature )";
$sql .= " SELECT user_name, feature FROM $todaysTableName";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "FAILED: $sql" );
}

// Clear todays table
$sql = "TRUNCATE TABLE $todaysTableName";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "FAILED: $sql" );
}
