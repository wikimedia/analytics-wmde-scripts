#!/usr/bin/php
<?php

/**
 * This script collects statistics about lexicographical data from the query service.
 *
 * Used by: https://grafana.wikimedia.org/d/UHV96YJGk/wikidata-datamodel-lexemes
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-sparql-lexemes' )->markStart();
$metrics = new WikidataLexemes();
$metrics->execute( $output );
$output->markEnd();

class WikidataLexemes {

	private const MAX_DETAILED_RESULTS = 50;

	public function execute( Output $output ) {
		$functions = [
			'countLexemesByLanguageItem',
			'countSensesByLanguageItem',
			'countFormsByLanguageItem',
			'countLexemesWithoutSensesByLanguageItem',
			'countLexemesWithoutFormsByLanguageItem',
			'countLexemesByLexicalCategoryItem',
			'countFormsByGrammaticalFeatureItem',
			'countLemmasByLanguageCode',
			'countRepresentationsByLanguageCode',
		];

		foreach ( $functions as $function ) {
			try {
				$this->$function();
				WikimediaSparql::sleepToAvoidRateLimit();
			} catch ( Exception $e ) {
				$output->outputMessage( "$function failed, skipping: " . $e->getMessage() );
			}
		}
	}

	private function countLexemesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(*) AS ?lexemes) WHERE {
  ?lexeme dct:language ?languageItem.
}
GROUP BY ?languageItem
ORDER BY DESC(?lexemes)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageItem', 'lexemes' );
	}

	private function countSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?sense) AS ?senses) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:sense ?sense. }
}
GROUP BY ?languageItem
ORDER BY DESC(?senses)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageItem', 'senses' );
	}

	private function countFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?form) AS ?forms) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:lexicalForm ?form. }
}
GROUP BY ?languageItem
ORDER BY DESC(?forms)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageItem', 'forms' );
	}

	private function countLexemesWithoutSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasSenses, 0, 1)) AS ?withoutSenses) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:sense ?sense. } AS ?hasSenses)
}
GROUP BY ?languageItem
ORDER BY DESC(?withoutSenses)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageItem', 'withoutSenses' );
	}

	private function countLexemesWithoutFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasForms, 0, 1)) AS ?withoutForms) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:lexicalForm ?form. } AS ?hasForms)
}
GROUP BY ?languageItem
ORDER BY DESC(?withoutForms)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageItem', 'withoutForms' );
	}

	private function countLexemesByLexicalCategoryItem() {
		$query = <<<'SPARQL'
SELECT ?lexicalCategoryItem (COUNT(*) AS ?lexemes) WHERE {
  ?lexeme wikibase:lexicalCategory ?lexicalCategoryItem.
}
GROUP BY ?lexicalCategoryItem
ORDER BY DESC(?lexemes)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'lexicalCategoryItem', 'lexemes' );
	}

	private function countFormsByGrammaticalFeatureItem() {
		$query = <<<'SPARQL'
SELECT ?grammaticalFeatureItem (COUNT(*) AS ?forms) WHERE {
  ?form wikibase:grammaticalFeature ?grammaticalFeatureItem.
}
GROUP BY ?grammaticalFeatureItem
ORDER BY DESC(?forms)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'grammaticalFeatureItem', 'forms' );
	}

	private function countLemmasByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?lemmas) WHERE {
  ?lexeme wikibase:lemma ?lemma.
  BIND(LANG(?lemma) AS ?languageCode)
}
GROUP BY ?languageCode
ORDER BY DESC(?lemmas)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageCode', 'lemmas' );
	}

	private function countRepresentationsByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?representations) WHERE {
  ?form ontolex:representation ?representation.
  BIND(LANG(?representation) AS ?languageCode)
}
GROUP BY ?languageCode
ORDER BY DESC(?representations)
SPARQL;
		$this->queryCountsAndSendToGraphite( $query, 'languageCode', 'representations' );
	}

	/**
	 * Run a SPARQL query to count elements by category, and send the results to Graphite.
	 *
	 * Results are sent to the metric daily.wikidata.datamodel.lexeme.$categoryName.$category.$countName,
	 * where $category is the value of the $categoryName variable of each result.
	 * Only the top self::MAX_DETAILED_RESULTS results are sent with their individual $category;
	 * all the remaining ones are summed together and sent in one metric,
	 * with “other” as the $categoryName, to avoid sending too many metrics to Graphite.
	 * (Note that the sorting of top results is expected to be done in the query!)
	 *
	 * @param string $query The SPARQL query.
	 * It should select variables named $categoryName and $countName,
	 * and be ordered by the $countName variable (descending).
	 * @param string $categoryName The name of the category,
	 * both in the SPARQL query and in the metric sent to Graphite.
	 * If the name ends in “Item”, each category returned by the query
	 * is turned into a plain ID with {@link WikimediaSparql::entityIriToId}.
	 * Examples: “languageItem”, “lexicalCategoryItem”, “languageCode”.
	 * @param string $countName The name of the count,
	 * both in the SPARQL query and in the metric sent to Graphite.
	 * Examples: “lexemes”, “senses”, “representations”.
	 */
	private function queryCountsAndSendToGraphite( string $query, string $categoryName, string $countName ) {
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		$categoryIsEntity = substr( $categoryName, -4 ) === 'Item'; // TODO use str_ends_with() in PHP 8
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top categories
				$category = $result[$categoryName]['value'];
				if ( $categoryIsEntity ) {
					$category = WikimediaSparql::entityIriToId( $category );
				}
				$count = $result[$countName]['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.$categoryName.$category.$countName", $count, $date );
				WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_lexeme_total',
				$count,
				[ 'categoryName' => $categoryName, 'category' => $category, 'countName' => $countName, 'targetDate' => $date ] );
			} else {
				$otherCount += (int)$result[$countName]['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining categories,
			// so that sum(daily.wikidata.datamodel.lexeme.$categoryName.*.$countName) is accurate
			WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.$categoryName.other.$countName", $otherCount, $date );
			WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_lexeme_total',
			$otherCount,
			[ 'categoryName' => $categoryName, 'category' => 'other', 'countName' => $countName, 'targetDate' => $date ] );
		}
	}

}
