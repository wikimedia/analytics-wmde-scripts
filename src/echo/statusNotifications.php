#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Sends data about echo mention status notification usage to graphite
 */

$userProperties = array(
	'failure' =>  'echo-subscriptions-web-mention-failure',
	'success' => 'echo-subscriptions-web-mention-success',
);

require_once( __DIR__ . '/../../lib/load.php' );
$output = Output::forScript( 'echo-statusNotifications' )->markStart();

$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/all.dblist' );
if( $dblist === false ) {
	throw new RuntimeException( 'Failed to get db list for echo status notification tracking!' );
}
$dbs = explode( "\n", $dblist[1] );
$dbs = array_filter( $dbs );

$pdo = WikimediaDb::getPdo();

$todaysTableName = 'staging.wmde_analytics_echoStatusNotif_users_today';
$yesterdayTableName = 'staging.wmde_analytics_echoStatusNotif_users_yesterday';

// Create todays table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS $todaysTableName";
$sql .= "( user_name VARCHAR(255) NOT NULL, property VARBINARY(255) NOT NULL, PRIMARY KEY (user_name, property) )";
$queryResult = $pdo->query( $sql );
if ( $queryResult === false ) {
	die( "Failed to create table $todaysTableName" );
}
// Create yesterday table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS $yesterdayTableName";
$sql .= "( user_name VARCHAR(255) NOT NULL, property VARBINARY(255) NOT NULL, PRIMARY KEY (user_name, property) )";
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

	// Record individuals into the temp table
	foreach( $userProperties as $metricName => $userProperty ) {
		$sql = "INSERT IGNORE INTO $todaysTableName ( user_name, property )";
		$sql .= " SELECT user_name, up_property FROM $dbname.user_properties";
		$sql .= " JOIN $dbname.user ON up_user = user_id";
		$sql .= " WHERE up_property = '$userProperty' AND up_value = '1'";
		$queryResult = $pdo->query( $sql );
		if( $queryResult === false ) {
			$output->outputMessage( "INSERT INTO FAILED for $dbname for property $userProperty, Skipping!!" );
		}
	}
}

// Select and send the global user counts (each global user is only counted once)
$sql = "SELECT COUNT(*) AS count, property";
$sql .= " FROM $todaysTableName";
$sql .= " GROUP BY property";
$queryResult = $pdo->query( $sql );
if( $queryResult === false ) {
	$output->outputMessage( "SELECT FROM temp table $todaysTableName FAILED!!" );
} else {
	foreach( $queryResult as $row ) {
		if ( in_array( $row['property'], $userProperties ) && $row['count'] > 0 ) {
		WikimediaGraphite::sendNow(
			'daily.echo.mentionStatus.global_user_counts.totals.' . $row['property'],
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
	$sql .= " WHERE ROW(today.user_name, today.property) NOT IN";
	$sql .= " ( SELECT * FROM $yesterdayTableName )";
	$sql .= " UNION ALL";
	$sql .= " SELECT 'disables' AS state, yesterday.* FROM $yesterdayTableName AS yesterday";
	$sql .= " WHERE ROW(yesterday.user_name, yesterday.property) NOT IN";
	$sql .= " ( SELECT * FROM $todaysTableName )";
	$sql = "SELECT state, COUNT(*) AS count, property FROM ( $sql ) AS a GROUP BY state, property";
	$queryResult = $pdo->query( $sql );
	if ( $queryResult === false ) {
		$output->outputMessage( "FAILED Intersection, Skipping!!" );
	} else {
		foreach( $queryResult as $row ) {
			WikimediaGraphite::sendNow(
				'daily.echo.mentionStatus.global_' . $row['state'] . '.totals.' . $row['property'],
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
$sql = "INSERT INTO $yesterdayTableName ( user_name, property )";
$sql .= " SELECT user_name, property FROM $todaysTableName";
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