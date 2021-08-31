#!/usr/bin/php
<?php

/**
 * Count edits having certain tags and send them to Graphite.
 * See T236893 for more information.
 * Used by: https://grafana.wikimedia.org/d/000000170/wikidata-edits
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-site_stats-recent_changes_by_tags' )->markStart();
$metrics = new WikidataRecentChangesByTags();
$metrics->execute( $output );
$output->markEnd();

class WikidataRecentChangesByTags {

	public function execute( Output $output ) {
		$dateTimeFrom = new DateTime( 'midnight 1 day ago UTC' );
		$dateTimeTo = new DateTime( 'midnight today UTC' );
		$editsByTags = [];

		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$pdoStatement = $pdo->prepare( file_get_contents( __DIR__ . '/sql/recent_changes_by_tags.sql' ) );
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

		while ( $row = $pdoStatement->fetch( PDO::FETCH_ASSOC ) ) {
			$editsByTags[$row['ctd_name']] = $row['count'];
		}

		foreach ( $editsByTags as $tag => $count ) {
			WikimediaGraphite::sendNow(
				'daily.wikidata.site_stats.edits_by_tags.' . $tag,
				$count
			);
		}
	}

}
