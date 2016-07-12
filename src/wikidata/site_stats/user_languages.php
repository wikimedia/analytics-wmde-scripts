#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$metrics = new WikidataUserLanguages();
$metrics->execute();

class WikidataUserLanguages{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		if( $pdo->query( "USE wikidatawiki" ) === false ) {
			throw new RuntimeException( "Failed to USE wikidatawiki db" );
		}

		$this->setUpTempTables( $pdo );
		$this->runBabelCountMetric( $pdo );
		$this->runLanguageUsageMetric( $pdo );
	}

	private function setUpTempTables( PDO $pdo ) {
		$filesToRunToSetup = array(
			'user_languages/tmptbl_babel_cats_level_1.sql',
			'user_languages/tmptbl_babel_cats_level_2.sql',
			'user_languages/tmptbl_user_babel_langs.sql',
			'user_languages/tmptbl_user_interface_langs.sql',
			'user_languages/tmptbl_user_interface_langs_no_babel.sql',
			'tmptbl_active_user_changes.sql',
		);

		foreach( $filesToRunToSetup as $fileName ) {
			if( $pdo->query( file_get_contents( __DIR__ . '/sql/' . $fileName ) ) === false ) {
				throw new RuntimeException( "Failed to run file " . $fileName );
			}
		}
	}

	private function runBabelCountMetric( PDO $pdo ) {
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/user_languages/select_babel_user_count.sql'
		) );
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
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/user_languages/select_language_usage.sql'
		) );
		if( $queryResult === false ) {
			throw new RuntimeException( "DB ERROR select_language_usage" );
		}
		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.language_usage." . $row['language'],
				$row['count']
			);
		}
	}

}
