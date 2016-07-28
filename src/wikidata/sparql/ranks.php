#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel-statements
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-sparql-ranks' )->markStart();
$metrics = new WikidataSparqlRanks();
$metrics->execute();
$output->markEnd();

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
				WikimediaGraphite::sendNow( 'daily.wikidata.datamodel.ranks.preferred', $rankCount );
			} elseif( array_key_exists( 'deprecated', $binding ) ) {
				$rankCount = $binding['deprecated']['value'];
				WikimediaGraphite::sendNow( 'daily.wikidata.datamodel.ranks.deprecated', $rankCount );
			} else {
				trigger_error( "SPARQL binding returned with unexpected keys " . json_encode( $binding ), E_USER_WARNING );
			}

		}

	}

}
