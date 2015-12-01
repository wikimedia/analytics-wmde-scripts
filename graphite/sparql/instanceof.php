#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );

$metrics = new WikidataInstanceOf();
$metrics->execute();

class WikidataInstanceOf{

	private $itemIds = array(
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
	);

	public function execute() {
		$results = array();
		foreach( $this->itemIds as $itemId ) {
			$results[$itemId] = $this->getResult( $itemId );
		}

		foreach( $results as $key => $value ) {
			$this->sendMetric( "daily.wikidata.datamodel.instanceof.$key", $value );
		}
	}

	private function getResult( $itemId ) {
		$query = "PREFIX wd: <http://www.wikidata.org/entity/>";
		$query .= "PREFIX wdt: <http://www.wikidata.org/prop/direct/>";
		$query .= "SELECT (count(distinct(?s)) AS ?scount) WHERE {";
		$query .= "?s wdt:P31/wdt:P279* wd:$itemId";
		$query .= "}";
		$result = $this->doSparqlQuery( $query );
		return $result['results']['bindings'][0]['scount']['value'];
	}

	/**
	 * @param string $query
	 *
	 * @return array
	 */
	private function doSparqlQuery ( $query ) {
		$response = WikimediaCurl::curlGet( "https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query ) );

		if( $response === false ) {
			throw new RuntimeException( "The SPARQL request failed!" );
		}

		return json_decode( $response, true );
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
