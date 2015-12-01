#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$metrics = new WikidataTermsByLanguage();
$metrics->execute();

class WikidataTermsByLanguage{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/select_terms_by_language.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$entityType = $row['term_entity_type'];
			$termType = $row['term_type'];
			$lang = $row['term_language'];

			$this->sendMetric(
				"daily.wikidata.datamodel.$entityType.$termType.$lang",
				$row['count']
			);
		}
	}


	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
