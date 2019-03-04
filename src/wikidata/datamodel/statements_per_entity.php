#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-datamodel-statements_per_entity' )->markStart();
$counter = new WikidataStatementCounter();
$counter->execute();
$output->markEnd();

/**
 * It should be noted that per the ItemContent and PropertyContent classes in Wikibase
 * the wb-claims page prop value will exist for all entities that are not redirects.
 * This includes entities that have 0 claims / statements.
 *
 * @author Addshore
 */
class WikidataStatementCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper());
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_statements_per_entity.sql'
		) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		$totals = [
			'item' => 0,
			'property' => 0,
		];
		$maxes = [
			'item' => 0,
			'property' => 0,
		];
		$entitiesWithStatements = [
			'item' => 0,
			'property' => 0,
		];
		foreach( $rows as $row ) {
			if( $row['namespace'] == '0' ) {
				$entityType = 'item';
			} elseif( $row['namespace'] == '120' ) {
				$entityType = 'property';
			} else {
				throw new LogicException( 'Couldn\'t identify namespace: ' . $row['namespace'] );
			}

			$totals[$entityType] += ( $row['statements'] * $row['count'] );
			$entitiesWithStatements[$entityType] += $row['count'];

			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.statements.count." . $row['statements'],
				$row['count']
			);

			if( $maxes[$entityType] < $row['statements'] ) {
				$maxes[$entityType] = $row['statements'];
			}
		}

		foreach( $totals as $entityType => $value ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.statements.total",
				$value
			);
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.statements.avg",
				$value / $entitiesWithStatements[$entityType]
			);
		}

		foreach( $maxes as $entityType => $value ) {
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.statements.max",
				$value
			);
			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.hasStatements",
				$entitiesWithStatements[$entityType]
			);
		}

	}

}
