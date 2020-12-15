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
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.lexemes", $count );
		}
	}

	private function countSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?sense) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:sense ?sense. }
}
GROUP BY ?languageItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.senses", $count );
		}
	}

	private function countFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (COUNT(?form) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  OPTIONAL { ?lexeme ontolex:lexicalForm ?form. }
}
GROUP BY ?languageItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.forms", $count );
		}
	}

	private function countLexemesWithoutSensesByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasSenses, 0, 1)) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:sense ?sense. } AS ?hasSenses)
}
GROUP BY ?languageItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.withoutSenses", $count );
		}
	}

	private function countLexemesWithoutFormsByLanguageItem() {
		$query = <<<'SPARQL'
SELECT ?languageItem (SUM(IF(?hasForms, 0, 1)) AS ?count) WHERE {
  ?lexeme dct:language ?languageItem.
  BIND(EXISTS { ?lexeme ontolex:lexicalForm ?form. } AS ?hasForms)
}
GROUP BY ?languageItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageItem = WikimediaSparql::entityIriToId( $result['languageItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageItem.$languageItem.withoutForms", $count );
		}
	}

	private function countLexemesByLexicalCategoryItem() {
		$query = <<<'SPARQL'
SELECT ?lexicalCategoryItem (COUNT(*) AS ?count) WHERE {
  ?lexeme wikibase:lexicalCategory ?lexicalCategoryItem.
}
GROUP BY ?lexicalCategoryItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$lexicalCategoryItem = WikimediaSparql::entityIriToId( $result['lexicalCategoryItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.lexicalCategoryItem.$lexicalCategoryItem.lexemes", $count );
		}
	}

	private function countFormsByGrammaticalFeatureItem() {
		$query = <<<'SPARQL'
SELECT ?grammaticalFeatureItem (COUNT(*) AS ?count) WHERE {
  ?form wikibase:grammaticalFeature ?grammaticalFeatureItem.
}
GROUP BY ?grammaticalFeatureItem
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$grammaticalFeatureItem = WikimediaSparql::entityIriToId( $result['grammaticalFeatureItem']['value'] );
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.grammaticalFeatureItem.$grammaticalFeatureItem.forms", $count );
		}
	}

	private function countLemmasByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?count) WHERE {
  ?lexeme wikibase:lemma ?lemma.
  BIND(LANG(?lemma) AS ?languageCode)
}
GROUP BY ?languageCode
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageCode = $result['languageCode']['value'];
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageCode.$languageCode.lemmas", $count );
		}
	}

	private function countRepresentationsByLanguageCode() {
		$query = <<<'SPARQL'
SELECT ?languageCode (COUNT(*) AS ?count) WHERE {
  ?form ontolex:representation ?representation.
  BIND(LANG(?representation) AS ?languageCode)
}
GROUP BY ?languageCode
SPARQL;
		$results = WikimediaSparql::query( $query );
		foreach ( $results['results']['bindings'] as $result ) {
			$languageCode = $result['languageCode']['value'];
			$count = $result['count']['value'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.lexeme.languageCode.$languageCode.representations", $count );
		}
	}

}
