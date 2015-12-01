#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );

$metrics = new WikidataSparqlRanks();
$metrics->execute();

class WikidataSparqlRanks{

	public function execute() {
		$query = "PREFIX wikibase: <http://wikiba.se/ontology#>";
		$query .= "SELECT * WHERE { {";
		$query .= "SELECT (count(distinct(?s)) AS ?preferred) WHERE {?s wikibase:rank wikibase:PreferredRank}";
		$query .= "} UNION {";
		$query .= "SELECT (count(distinct(?s)) AS ?deprecated) WHERE {?s wikibase:rank wikibase:DeprecatedRank}";
		$query .= "} }";

		$response = WikimediaCurl::curlGet( "https://query.wikidata.org/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query ) );

		if( $response === false ) {
			throw new RuntimeException( "The SPARQL request failed!" );
		}

		$data = json_decode( $response[1], true );

		foreach( $data['results']['bindings'] as $binding ) {

			if( array_key_exists( 'preferred', $binding ) ) {
				$rankCount = $binding['preferred']['value'];
				exec( "echo \"daily.wikidata.datamodel.ranks.preferred $rankCount `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
			} elseif( array_key_exists( 'deprecated', $binding ) ) {
				$rankCount = $binding['deprecated']['value'];
				exec( "echo \"daily.wikidata.datamodel.ranks.deprecated $rankCount `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
			} else {
				trigger_error( "SPARQL binding returned with unexpected keys " . json_encode( $binding ), E_USER_WARNING );
			}

		}

	}

}
