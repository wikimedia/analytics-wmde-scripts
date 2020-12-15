#!/usr/bin/php
<?php

/**
 * This script collects statistics about lexicographical data from the query service.
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
SELECT ?languageItem (COUNT(*) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
}
GROUP BY ?languageItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language items
				$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.lexemes", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language items,
			// so that sum(daily.wikidata.datamodel.lexemes.languageItem.*.lexemes) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageItem.other.lexemes', $otherCount, $date );
		}
	}

	private function countSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?sense) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:sense ?sense. }
}
GROUP BY ?languageItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language items
				$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.senses", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language items,
			// so that sum(daily.wikidata.datamodel.lexemes.languageItem.*.senses) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageItem.other.senses', $otherCount, $date );
		}
	}

	private function countFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?form) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:lexicalForm ?form. }
}
GROUP BY ?languageItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language items
				$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.forms", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language items,
			// so that sum(daily.wikidata.datamodel.lexemes.languageItem.*.forms) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageItem.other.forms', $otherCount, $date );
		}
	}

	private function countLexemesWithoutSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasSenses, 0, 1)) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:sense ?sense. } AS ?hasSenses)
}
GROUP BY ?languageItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language items
				$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.withoutSenses", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language items,
			// so that sum(daily.wikidata.datamodel.lexemes.languageItem.*.withoutSenses) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageItem.other.withoutSenses', $otherCount, $date );
		}
	}

	private function countLexemesWithoutFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasForms, 0, 1)) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:lexicalForm ?form. } AS ?hasForms)
}
GROUP BY ?languageItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language items
				$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.withoutForms", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language items,
			// so that sum(daily.wikidata.datamodel.lexemes.languageItem.*.withoutForms) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageItem.other.withoutForms', $otherCount, $date );
		}
	}

	private function countLexemesByLexicalCategoryItem() {
		$query = <<<'SPARQL'
SELECT ?lexicalCategoryItem (COUNT(*) AS ?count) WHERE {
  ?lexeme wikibase:lexicalCategory ?lexicalCategoryItem.
}
GROUP BY ?lexicalCategoryItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top lexical category items
				$lexicalCategoryItem = WikimediaSparql::entityIriToId( $result['lexicalCategoryItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.lexicalCategoryItem.$lexicalCategoryItem.lexemes", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining lexical category items,
			// so that sum(daily.wikidata.datamodel.lexemes.lexicalCategoryItem.*.lexemes) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.lexicalCategoryItem.other.lexemes', $otherCount, $date );
		}
	}

	private function countFormsByGrammaticalFeatureItem() {
		$query = <<<'SPARQL'
SELECT ?grammaticalFeatureItem (COUNT(*) AS ?count) WHERE {
  ?form wikibase:grammaticalFeature ?grammaticalFeatureItem.
}
GROUP BY ?grammaticalFeatureItem
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top grammatical feature items
				$grammaticalFeatureItem = WikimediaSparql::entityIriToId( $result['grammaticalFeatureItem']['value'] );
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.grammaticalFeatureItem.$grammaticalFeatureItem.forms", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining grammatical feature items,
			// so that sum(daily.wikidata.datamodel.lexemes.grammaticalFeatureItem.*.forms) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.grammaticalFeatureItem.other.forms', $otherCount, $date );
		}
	}

	private function countLemmasByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?count) WHERE {
  ?lexeme wikibase:lemma ?lemma.
  BIND(LANG(?lemma) AS ?languageCode)
}
GROUP BY ?languageCode
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language codes
				$languageCode = $result['languageCode']['value'];
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageCode.$languageCode.lemmas", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language codes,
			// so that sum(daily.wikidata.datamodel.lexemes.languageCode.*.lemmas) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageCode.other.lemmas', $otherCount, $date );
		}
	}

	private function countRepresentationsByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?count) WHERE {
  ?form ontolex:representation ?representation.
  BIND(LANG(?representation) AS ?languageCode)
}
GROUP BY ?languageCode
ORDER BY DESC(?count)
SPARQL;
		$date = date( DATE_ATOM );
		$results = WikimediaSparql::query( $query );
		$sentResults = 0;
		$otherCount = 0;
		foreach ( $results['results']['bindings'] as $result ) {
			if ( ++$sentResults <= self::MAX_DETAILED_RESULTS ) { // send detailed metrics for top language codes
				$languageCode = $result['languageCode']['value'];
				$count = $result['count']['value'];
				WikimediaGraphite::send( "daily.wikidata.datamodel.lexeme.languageCode.$languageCode.representations", $count, $date );
			} else {
				$otherCount += (int)$result['count']['value'];
			}
		}
		if ( $sentResults > self::MAX_DETAILED_RESULTS ) {
			// send an aggregate count for the remaining language codes,
			// so that sum(daily.wikidata.datamodel.lexemes.languageCode.*.representations) is accurate
			WikimediaGraphite::send( 'daily.wikidata.datamodel.lexeme.languageCode.other.representations', $otherCount, $date );
		}
	}

}
