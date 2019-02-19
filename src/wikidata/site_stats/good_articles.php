#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-good_articles' )->markStart();
$metrics = new WikidataGoodArticles();
$metrics->execute();
$output->markEnd();

class WikidataGoodArticles{

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( 'wikidatawiki', new WikimediaDbSectionMapper() );
		$result = $pdo->query( "select ss_good_articles from wikidatawiki.site_stats" );

		if( $result === false ) {
			throw new RuntimeException( "Something went wrong with the db query for good_articles" );
		}
		$rows = $result->fetchAll();
		$count = $rows[0]['ss_good_articles'];
		WikimediaGraphite::sendNow( "daily.wikidata.site_stats.good_articles", $count );
	}

}
