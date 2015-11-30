#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

$metrics = new WikidataReferences();
$metrics->execute();

class WikidataReferences{

	public function execute() {
		$propertyIds = $this->getReferencePropertyIds();

		$counts = array();
		foreach( $propertyIds as $propertyId ) {
			$count = $this->getReferenceCount( $propertyId );
			$counts[$propertyId] = $count;
		}

		foreach( $counts as $key => $value ) {
			$this->sendMetric( "daily.wikidata.datamodel.references.$key", $value );
		}
	}

	private function getReferenceCount( $propertyId ) {
		$query = "Prefix prov: <http://www.w3.org/ns/prov#>";
		// TODO this count should be distinct
		$query .= "SELECT (count(?s) AS ?scount) WHERE {";
		$query .= "?wdref <http://www.wikidata.org/prop/reference/$propertyId> ?x .";
		$query .= "?s prov:wasDerivedFrom ?wdref";
		$query .= "}";

		$result = $this->doSparqlQuery( $query );
		return $result['results']['bindings'][0]['scount']['value'];
	}

	private function getReferencePropertyIds() {
		$query = "PREFIX wd: <http://www.wikidata.org/entity/>";
		$query .= "PREFIX wdt: <http://www.wikidata.org/prop/direct/>";
		// Q18608359 = Wikidata property to indicate a source
		$query .= "SELECT ?s WHERE {?s wdt:P31/wdt:P279* wd:Q18608359}";

		$result = $this->doSparqlQuery( $query );

		$propertyIds = array();
		foreach( $result['results']['bindings'] as $binding ) {
			$itemId = str_replace( 'http://www.wikidata.org/entity/', '', $binding['s']['value'] );
			$propertyIds[] = $itemId;
		}
		return $propertyIds;
	}

	/**
	 * @param string $query
	 *
	 * @return array
	 */
	private function doSparqlQuery ( $query ) {
		$response = $this->file_get_contents( "https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query ) );

		if( $response === false ) {
			throw new RuntimeException( "The SPARQL request failed!" );
		}

		return json_decode( $response, true );
	}

	private function file_get_contents( $filename ) {
		$opts = array(
			'http' => array(
				'method' => "GET",
				'header' => "User-Agent: WMDE Wikidata metrics gathering\r\n",
			),
		);

		$context = stream_context_create( $opts );

		return file_get_contents( $filename, false, $context );
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
