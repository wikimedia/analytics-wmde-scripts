#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-datamodel-sitelinks_per_site' )->markStart();
$counter = new WikidataSiteSitelinkCounter();
$counter->execute();
$output->markEnd();

class WikidataSiteSitelinkCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_sitelinks_per_site.sql'
		) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.item.sitelinks.sites." . $row['site'],
				$row['count']
			);
		}
	}

}
