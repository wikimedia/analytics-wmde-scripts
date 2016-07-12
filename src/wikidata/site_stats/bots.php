#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataBots();
$metrics->execute();

class WikidataBots{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$result = $pdo->query( "select count(*) as count from user_groups where ug_group = 'bot' group by ug_group" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for bots" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['count'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.user_groups.bots", $count );
	}

}
