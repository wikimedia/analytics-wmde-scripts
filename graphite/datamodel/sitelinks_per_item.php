#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$counter = new WikidataItemSitelinkCounter();
$counter->execute();

class WikidataItemSitelinkCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/select_sitelinks_per_item.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		$max = 0;
		$sitelinks = 0;
		$itemsWithSitelinks = 0;
		foreach( $rows as $row ) {
			$this->sendMetric(
				"daily.wikidata.datamodel.item.sitelinks.count." . $row['sitelinks'],
				$row['count']
			);
			$itemsWithSitelinks += $row['count'];
			$sitelinks += ( $row['sitelinks'] * $row['count'] );
			if( $row['sitelinks'] > $max ) {
				$max = $row['sitelinks'];
			}
		}

		$this->sendMetric(
			"daily.wikidata.datamodel.item.sitelinks.max",
			$max
		);
		$this->sendMetric(
			"daily.wikidata.datamodel.item.sitelinks.avg",
			$sitelinks / $itemsWithSitelinks
		);
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
