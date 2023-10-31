#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000175/wikidata-datamodel-statements
 */

require_once __DIR__ . '/../../../lib/load.php';
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
class WikidataStatementCounter {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_statements_per_entity.sql'
		) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		$totals = [
			'item' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
			'property' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
		];
		$maxes = [
			'item' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
			'property' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
		];
		$entitiesWithStatements = [
			'item' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
			'property' => [ 'statements' => 0, 'wb-identifiers' => 0 ],
		];
		foreach ( $rows as $row ) {
			if ( $row['namespace'] == '0' ) {
				$entityType = 'item';
			} elseif ( $row['namespace'] == '120' ) {
				$entityType = 'property';
			} else {
				throw new LogicException( 'Couldn\'t identify namespace: ' . $row['namespace'] );
			}

			if ( $row['pp_propname'] == 'wb-claims' ) {
				$type = 'statements';
			} else {
				$type = $row['pp_propname'];
			}

			$totals[$entityType][$type] += ( $row['statements'] * $row['count'] );
			$entitiesWithStatements[$entityType][$type] += $row['count'];

			WikimediaGraphite::sendNow(
				"daily.wikidata.datamodel.$entityType.$type.count." . $row['statements'],
				$row['count']
			);

			if ( $maxes[$entityType][$type] < $row['statements'] ) {
				$maxes[$entityType][$type] = $row['statements'];
			}
		}

		foreach ( $totals as $entityType => $data ) {
			foreach ( $data as $type => $value ) {
				WikimediaGraphite::sendNow(
					"daily.wikidata.datamodel.$entityType.$type.total",
					$value
				);
				if ( $entitiesWithStatements[$entityType][$type] !== 0 ) {
					WikimediaGraphite::sendNow(
						"daily.wikidata.datamodel.$entityType.$type.avg",
						$value / $entitiesWithStatements[$entityType][$type]
					);
				}
			}
		}

		foreach ( $maxes as $entityType => $data ) {
			foreach ( $data as $type => $value ) {
				WikimediaGraphite::sendNow(
					"daily.wikidata.datamodel.$entityType.$type.max",
					$value
				);
				WikimediaGraphite::sendNow(
					"daily.wikidata.datamodel.$entityType.hasStatements",
					$entitiesWithStatements[$entityType][$type]
				);
			}

		}
	}

}
