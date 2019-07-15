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
$metrics = new WikidataInstanceOf(
	Config::getValue( 'wdqs_host' )
);
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

	/** @var string $wdqsHost */
	private $wdqsHost;

	/**
	 * @param string $wdqsHost
	 */
	public function __construct( $wdqsHost ) {
		$this->wdqsHost = $wdqsHost;
	}

	public function execute() {
		$results = [];
		foreach ( $this->itemIds as $itemId ) {
			$results[$itemId] = $this->getResult( $itemId );
			$this->sleepToAvoidRateLimit();
		}

		foreach ( $results as $key => $value ) {
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.instanceof.$key", $value );
		}
	}

	private function sleepToAvoidRateLimit() {
		sleep( 2 );
	}

	private function getResult( $itemId ) {
		$query = 'PREFIX wd: <http://www.wikidata.org/entity/>';
		$query .= 'PREFIX wdt: <http://www.wikidata.org/prop/direct/>';
		$query .= 'SELECT (count(distinct(?s)) AS ?scount) WHERE {';
		$query .= "?s wdt:P31/wdt:P279* wd:$itemId";
		$query .= '}';
		$result = $this->doSparqlQuery( $query );
		return $result['results']['bindings'][0]['scount']['value'];
	}

	/**
	 * @param string $query
	 *
	 * @return array
	 */
	private function doSparqlQuery( $query ) {
		/**
		 * Access to wdqs1003 from the analytics stat* machines is allowed by firewall rules.
		 * @see https://phabricator.wikimedia.org/T198623#4396997
		 */
		$response = WikimediaCurl::curlGetInternal(
			"{$this->wdqsHost}/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query )
		);

		if ( $response === false ) {
			throw new RuntimeException( 'The SPARQL request failed!' );
		}

		Output::forScript( 'wikidata-sparql-instanceof' )->outputMessage(
			__METHOD__ . ': ' . $query . ' ' . json_encode( $response )
		);

		return json_decode( $response[1], true );
	}

}
