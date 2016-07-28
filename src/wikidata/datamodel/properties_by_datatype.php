#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-datamodel
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-datamodel-properties_by_datatype' )->markStart();
$metrics = new WikidataPropertiesByDataType();
$metrics->execute();
$output->markEnd();

class WikidataPropertiesByDataType{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_properties_by_datatype.sql'
		) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$type = $row['type'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.property.datatype.$type", $row['count'] );
		}
	}

}
