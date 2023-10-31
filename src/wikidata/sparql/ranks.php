#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * Used by: https://grafana.wikimedia.org/d/000000175/wikidata-datamodel-statements
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-sparql-ranks' )->markStart();
$metrics = new WikidataSparqlRanks();
$metrics->execute();
$output->markEnd();

class WikidataSparqlRanks {

	public function execute() {
		$query = <<<'SPARQL'
PREFIX wikibase: <http://wikiba.se/ontology#>
SELECT * WHERE { {
  SELECT (COUNT(*) AS ?preferred) WHERE {?s wikibase:rank wikibase:PreferredRank}
} UNION {
  SELECT (COUNT(*) AS ?deprecated) WHERE {?s wikibase:rank wikibase:DeprecatedRank}
} }
SPARQL;

		$data = WikimediaSparql::query( $query );

		foreach ( $data['results']['bindings'] as $binding ) {

			if ( array_key_exists( 'preferred', $binding ) ) {
				$rankCount = $binding['preferred']['value'];
				WikimediaGraphite::sendNow( 'daily.wikidata.datamodel.ranks.preferred', $rankCount );
			} elseif ( array_key_exists( 'deprecated', $binding ) ) {
				$rankCount = $binding['deprecated']['value'];
				WikimediaGraphite::sendNow( 'daily.wikidata.datamodel.ranks.deprecated', $rankCount );
			} else {
				trigger_error( 'SPARQL binding returned with unexpected keys ' . json_encode( $binding ), E_USER_WARNING );
			}

		}
	}

}
