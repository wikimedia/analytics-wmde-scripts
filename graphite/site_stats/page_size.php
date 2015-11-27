#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$metrics = new WikidataPageSizes();
$metrics->execute();

class WikidataPageSizes{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/page_size.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$namespace = $row['namespace'];
			$this->sendMetric(
				"daily.wikidata.site_stats.page_length.$namespace.avg",
				$row['avg']
			);
			$this->sendMetric(
				"daily.wikidata.site_stats.page_length.$namespace.max",
				$row['max']
			);

		}
	}


	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
