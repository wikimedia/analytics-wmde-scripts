#!/usr/bin/php
<?php

/**
 * @author Amir Sarabadani
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-sparql-constraint-snaks' )->markStart();
$metrics = new WikidataConstraintsByType();
$metrics->execute();
$output->markEnd();

class WikidataConstraintsByType {

	public function execute() {
		$query = <<<EOF
SELECT ?type (COUNT(DISTINCT ?constraint) AS ?count) WHERE {
  ?property a wikibase:Property;
            p:P2302 ?constraint.
  ?constraint ps:P2302 ?type.
  MINUS { ?constraint wikibase:rank wikibase:DeprecatedRank. }
}
GROUP BY ?type
EOF;

		$response = WikimediaCurl::curlGetWithRetryExternal( "https://query.wikidata.org/sparql?format=json&query=" . urlencode( $query ) );

		if( $response === false ) {
			throw new RuntimeException( "The SPARQL request failed!" );
		}

		$data = json_decode( $response[1], true );

		foreach( $data['results']['bindings'] as $binding ) {
			$this->handleBinding( $binding );
		}

	}

	private function handleBinding( $binding ) {
		if( !array_key_exists( 'count', $binding ) || !array_key_exists( 'type', $binding ) ) {
			return;
		}
		$constraintCount = $binding['count']['value'];
		$constraint = str_replace( "http://www.wikidata.org/entity/", '', $binding['type']['value'] );
		WikimediaGraphite::sendNow( 'daily.wikidata.constraints.byType.' . $constraint, (int)$constraintCount );
	}

}
