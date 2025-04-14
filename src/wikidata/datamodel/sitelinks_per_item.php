#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000167/wikidata-datamodel
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-datamodel-sitelinks_per_item' )->markStart();
$counter = new WikidataItemSitelinkCounter();
$counter->execute();
$output->markEnd();

class WikidataItemSitelinkCounter {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_sitelinks_per_item.sql'
		) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		$max = 0;
		$sitelinks = 0;
		$itemsWithSitelinks = 0;
		foreach ( $rows as $row ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.datamodel.item.sitelinks.count.' . $row['sitelinks'],
				$row['count']
			);
			WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_item_sitelinks_count', $row['count'], [ 'sitelinks' => $row['sitelinks'] ] );
			$itemsWithSitelinks += $row['count'];
			$sitelinks += ( $row['sitelinks'] * $row['count'] );
			if ( $row['sitelinks'] > $max ) {
				$max = $row['sitelinks'];
			}
		}

		WikimediaGraphite::sendNow(
			'daily.wikidata.datamodel.item.sitelinks.max',
			$max
		);
		WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_item_sitelinks_max', $max );
		WikimediaGraphite::sendNow(
			'daily.wikidata.datamodel.item.sitelinks.avg',
			$sitelinks / $itemsWithSitelinks
		);
		WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_item_sitelinks_avg', $sitelinks / $itemsWithSitelinks );
		WikimediaGraphite::sendNow(
			'daily.wikidata.datamodel.item.hasSitelinks',
			$itemsWithSitelinks
		);
		WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_item_hasSitelinks_total', $itemsWithSitelinks );
	}

}
