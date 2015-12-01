#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$counter = new WikidataSiteSitelinkCounter();
$counter->execute();

class WikidataSiteSitelinkCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/select_sitelinks_per_site.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$this->sendMetric(
				"daily.wikidata.datamodel.item.sitelinks.sites." . $row['site'],
				$row['count']
			);
		}
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
