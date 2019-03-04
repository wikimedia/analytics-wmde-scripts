#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel-terms
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-datamodel-terms_by_language' )->markStart();
$metrics = new WikidataTermsByLanguage();
$metrics->execute();
$output->markEnd();

class WikidataTermsByLanguage {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_terms_by_language.sql'
		) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		foreach ( $rows as $row ) {
			$entityType = $row['term_entity_type'];
			$termType = $row['term_type'];
			$lang = $row['term_language'];

			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.$termType.$lang",
				$row['count']
			);
		}
	}

}
