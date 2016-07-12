#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$counter = new WikidataItemSitelinkCounter();
$counter->execute();

class WikidataItemSitelinkCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_sitelinks_per_item.sql'
		) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		$max = 0;
		$sitelinks = 0;
		$itemsWithSitelinks = 0;
		foreach( $rows as $row ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.item.sitelinks.count." . $row['sitelinks'],
				$row['count']
			);
			$itemsWithSitelinks += $row['count'];
			$sitelinks += ( $row['sitelinks'] * $row['count'] );
			if( $row['sitelinks'] > $max ) {
				$max = $row['sitelinks'];
			}
		}

		WikimediaGraphite::sendNow(
			"daily.wikidata.datamodel.item.sitelinks.max",
			$max
		);
		WikimediaGraphite::sendNow(
			"daily.wikidata.datamodel.item.sitelinks.avg",
			$sitelinks / $itemsWithSitelinks
		);
		WikimediaGraphite::sendNow(
			"daily.wikidata.datamodel.item.hasSitelinks",
			$itemsWithSitelinks
		);
	}

}
