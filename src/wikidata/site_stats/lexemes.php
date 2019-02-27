#!/usr/bin/php
<?php

/**
 * @author Ladsgroup
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-site-stats
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-site_stats-lexemes' )->markStart();
$metrics = new WikidataLexemes();
$metrics->execute();
$output->markEnd();

class WikidataLexemes{

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper());
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/lexemes.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.site_stats.lexemes." . $row['pp_propname'],
				$row['count']
			);
		}
	}

}
