<?php

/**
 * @author Addshore
 */

$metrics = new WikidataSparqlTriples();
$metrics->execute();

class WikidataSparqlTriples{

	public function execute() {
		// WDQS currently caches for 120 seconds, avoid this by adding whitespace
		$whiteSpace = str_repeat( ' ', date( 'i' ) );

		$query = "SELECT ( COUNT( * ) AS ?no ) { ?s ?p ?o $whiteSpace}";
		$response = file_get_contents( "https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query ) );

		if( $response === false ) {
			throw new RuntimeException( "The request failed!" );
		}

		$data = json_decode( $response, true );
		$value = $data['results']['bindings'][0]['no']['value'];

		exec( "echo \"wikidata.query.triples $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
