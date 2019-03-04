#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-user_languages' )->markStart();
$metrics = new WikidataUserLanguages();
$metrics->execute();
$output->markEnd();

class WikidataUserLanguages{

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper());

		if( $pdo->query( "USE wikidatawiki" ) === false ) {
			throw new RuntimeException( "Failed to USE wikidatawiki db" );
		}

		$this->runBabelCountMetric( $pdo );
		$this->runLanguageUsageMetric( $pdo );
	}

	private function runBabelCountMetric( PDO $pdo ) {
		$queryResult = $pdo->query( 'SELECT COUNT(DISTINCT babel_user) AS count FROM babel' );
		if( $queryResult === false ) {
			throw new RuntimeException( "DB ERROR select_babel_user_count" );
		}
		$rows = $queryResult->fetchAll();
		foreach( $rows as $row ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.babel_users",
				$row['count']
			);
		}
	}

	private function runLanguageUsageMetric( PDO $pdo ) {
		$endResults = [];

		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/user_languages/user_babel_langs.sql' ));
		foreach( $queryResult->fetchAll() as $row ) {
			$endResults[$row['babel_lang']] = (integer)$row['count'];
		}

		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/user_languages/user_interface_langs.sql') );
		foreach( $queryResult->fetchAll() as $row ) {
			if ( isset( $endResults[$row['language']] ) ) {
				$endResults[$row['language']] += (integer)$row['count'];
			} else {
				$endResults[$row['language']] = (integer)$row['count'];
			}

		}

		$queryResult = $pdo->query( file_get_contents(__DIR__ . '/sql/user_languages/select_babel_and_interface_user_count.sql') );
		// Inclusion–exclusion principle
		foreach( $queryResult->fetchAll() as $row ) {
			$endResults[$row['language']] -= (integer)$row['count'];
		}

		foreach( $endResults as $lang => $count ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.language_usage.$lang",
				$count
			);
		}
	}

}
