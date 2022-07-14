#!/usr/bin/php
<?php

/**
 * @author Ladsgroup
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-lexemes' )->markStart();
$metrics = new WikidataLexemes();
$metrics->execute();
$output->markEnd();

class WikidataLexemes {

	public function execute() {
		$this->collectLexemePagePropStats();
		$this->collectLexemesCreatedViaUI();
	}

	private function collectLexemesCreatedViaUI() {
		$dateTimeFrom = new DateTime( 'midnight 1 day ago UTC' );
		$dateTimeTo = new DateTime( 'midnight today UTC' );

		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$pdoStatement = $pdo->prepare( file_get_contents( __DIR__ . '/sql/recent_changes_new_lexemes_via_ui.sql' ) );
		$queryResult = $pdoStatement->execute(
			[
				$dateTimeFrom->format( 'YmdHis' ),
				$dateTimeTo->format( 'YmdHis' ),
			]
		);

		if ( $queryResult === false ) {
			$output->outputMessage( 'DB query failed:' );
			$output->outputMessage( var_export( $pdoStatement->errorInfo(), true ) );
			$output->outputMessage( 'Skipping!' );
			return;
		}

		$row = $pdoStatement->fetch( PDO::FETCH_ASSOC );
		WikimediaGraphite::sendNow(
			'daily.wikidata.site_stats.lexemes.new.ui',
			$row['count']
		);
	}

	private function collectLexemePagePropStats() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/lexemes.sql' ) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		foreach ( $rows as $row ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.lexemes.' . $row['pp_propname'],
				$row['count']
			);
		}
	}

}
