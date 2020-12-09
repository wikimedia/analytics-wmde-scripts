<?php

/**
 * Convenience functions for running SPARQL queries
 */
class WikimediaSparql {

	/**
	 * Run a SPARQL query against the configured query service host.
	 *
	 * For a SELECT query, the result looks like this:
	 *
	 *     [
	 *         'head' => [ 'vars' => [ ... ] ],
	 *         'results' => [ 'bindings' => [
	 *             [ ... ],
	 *             ...
	 *         ],
	 *     ]
	 *
	 * Other query forms like ASK have different result formats (but are seldom used).
	 *
	 * Scripts which call this function in a loop should also call
	 * {@link sleepToAvoidRateLimit} each iteration.
	 *
	 * @param string $query
	 * @return array
	 */
	public static function query( string $query ): array {
		$wdqsHost = Config::getValue( 'wdqs_host' );

		/**
		 * Access to wdqs* from the analytics stat* machines is allowed by firewall rules.
		 * @see https://phabricator.wikimedia.org/T198623#4396997
		 */
		$response = WikimediaCurl::curlGetInternal(
			"$wdqsHost/bigdata/namespace/wdq/sparql?format=json&query=" . urlencode( $query )
		);

		if ( $response === false ) {
			throw new RuntimeException( 'The SPARQL request failed!' );
		}

		return json_decode( $response[1], true );
	}

	/**
	 * Turn an entity IRI like <http://www.wikidata.org/entity/Q42> into an ID like "Q42".
	 *
	 * @param string $iri
	 * @return string
	 */
	public static function entityIriToId( string $iri ): string {
		return str_replace( 'http://www.wikidata.org/entity/', '', $iri );
	}

	/**
	 * Sleep for a while, to avoid violating the query service rate limit.
	 *
	 * Calling this function is recommended for scripts which call {@link query} in a loop.
	 */
	public static function sleepToAvoidRateLimit(): void {
		sleep( 2 );
	}

}
