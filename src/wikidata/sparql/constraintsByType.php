#!/usr/bin/php
<?php

/**
 * @author Amir Sarabadani
 * Used by: https://grafana.wikimedia.org/d/000000344/wikidata-quality-constraints-wbqc?viewPanel=12
 */

require_once __DIR__ . '/../../../lib/load.php';
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

		$data = WikimediaSparql::query( $query );

		foreach ( $data['results']['bindings'] as $binding ) {
			$this->handleBinding( $binding );
		}
	}

	private function handleBinding( array $binding ) {
		if ( !array_key_exists( 'count', $binding ) || !array_key_exists( 'type', $binding ) ) {
			return;
		}
		$constraintCount = $binding['count']['value'];
		$constraint = WikimediaSparql::entityIriToId( $binding['type']['value'] );
		WikimediaGraphite::sendNow( 'daily.wikidata.constraints.byType.' . $constraint, (int)$constraintCount );
		WikimediaStatsdExporter::sendNow( 'daily_wikidata_constraints_byType_total', (int)$constraintCount, [ 'type' => $constraint ] );
	}

}
