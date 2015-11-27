#!/usr/bin/php
<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$counter = new WikidataStatementCounter();
$counter->execute();

class WikidataStatementCounter{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/statements_per_entity.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			if( $row['namespace'] == '0' ) {
				$entityType = 'item';
			} elseif( $row['namespace'] == '120' ) {
				$entityType = 'property';
			} else {
				throw new LogicException( 'Couldn\'t identify namespace: ' . $row['namespace'] );
			}

			$this->sendMetric(
				"daily.wikidata.datamodel.$entityType.statements.count." . $row['statements'],
				$row['count']
			);
		}
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}