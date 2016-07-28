#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-users' )->markStart();
$metrics = new WikidataUsers();
$metrics->execute();
$output->markEnd();

class WikidataUsers{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$result = $pdo->query( "select ss_users from wikidatawiki.site_stats" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for users" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['ss_users'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.users", $count );
	}

}
