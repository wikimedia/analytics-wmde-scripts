#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-user_groups' )->markStart();

// Map of group name => metric name
$groupMap = array(
	'sysop' => 'admins',
	'bureaucrat' => 'bureaucrats',
	'bot' => 'bots',
	'propertycreator' => 'propertycreators',
);

$metrics = new WikidataUserGroups();
$metrics->execute( $groupMap, $output );
$output->markEnd();

class WikidataUserGroups{

	public function execute( array $groupMap, Output $output ) {
		$pdo = WikimediaDb::getPdo();
		foreach ( $groupMap as $group => $metricName ) {
			$output->outputMessage( "Running query for $metricName group" );
			$result = $pdo->query(
				"SELECT count(*) AS count FROM wikidatawiki.user_groups WHERE ug_group = '$group' GROUP BY ug_group"
			);

			if( $result === false ) {
				$output->outputMessage( "DB query for $metricName failed" );
				continue;
			}
			$rows = $result->fetchAll();
			$count = $rows[0]['count'];
			WikimediaGraphite::sendNow( "daily.wikidata.site_stats.user_groups.$metricName", $count );
		}
	}

}
