#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Sends the number of different instances of items that are on Wikidata based on the result of a
 * SPARQL query.
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel-statements
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-sparql-instanceof' )->markStart();
$metrics = new WikidataInstanceOf();
$metrics->execute();
$output->markEnd();

class WikidataInstanceOf {

	private $itemIds = [
		'Q11266439', // template
		'Q4167836', // category
		'Q15184295', // module
		'Q16521', // taxon
		'Q11173', // chemical compound
		'Q5', // human
		'Q56061', // administrative unit
		'Q1190554', // event
		'Q811979', // architectural structure
		'Q13406463', // list
		'Q4167410', // disambiguation
		'Q11424', // film
		'Q83620', // thoroughfare
		'Q6999', // astronomical object
		'Q16686448', // other artificial object
	];

	public function execute() {
		$results = [];
		foreach ( $this->itemIds as $itemId ) {
			$results[$itemId] = $this->getResult( $itemId );
			WikimediaSparql::sleepToAvoidRateLimit();
		}

		foreach ( $results as $key => $value ) {
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.instanceof.$key", $value );
		}
	}

	private function getResult( $itemId ) {
		$query = <<<SPARQL
PREFIX wd: <http://www.wikidata.org/entity/>
PREFIX wdt: <http://www.wikidata.org/prop/direct/>
SELECT (count(distinct(?s)) AS ?scount) WHERE {
  ?s wdt:P31/wdt:P279* wd:$itemId.
}
SPARQL;

		$result = WikimediaSparql::query( $query );
		Output::forScript( 'wikidata-sparql-instanceof' )->outputMessage(
			__METHOD__ . ': ' . $query . ' ' . json_encode( $result )
		);

		return $result['results']['bindings'][0]['scount']['value'];
	}

}
