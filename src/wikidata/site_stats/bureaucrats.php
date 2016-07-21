#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataBureaucrats();
$metrics->execute();

class WikidataBureaucrats{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$result = $pdo->query( "select count(*) as count from wikidatawiki.user_groups where ug_group = 'bureaucrat' group by ug_group" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for bureaucrats" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['count'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.user_groups.bureaucrats", $count );
	}

}
