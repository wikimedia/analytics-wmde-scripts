#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Used by: https://grafana.wikimedia.org/d/000000167/wikidata-datamodel?viewPanel=6
 */

require_once __DIR__ . '/../../../lib/load.php';
$output = Output::forScript( 'wikidata-datamodel-properties_by_datatype' )->markStart();
$metrics = new WikidataPropertiesByDataType();
$metrics->execute();
$output->markEnd();

class WikidataPropertiesByDataType {

	public function execute() {
		$pdo = WikimediaDb::getPdoNewHosts( WikimediaDb::WIKIDATA_DB, new WikimediaDbSectionMapper() );
		$queryResult = $pdo->query( file_get_contents(
			__DIR__ . '/sql/select_properties_by_datatype.sql'
		) );

		if ( $queryResult === false ) {
			throw new RuntimeException( 'Something went wrong with the db query' );
		}

		$rows = $queryResult->fetchAll();

		foreach ( $rows as $row ) {
			$type = $row['type'];
			WikimediaGraphite::sendNow( "daily.wikidata.datamodel.property.datatype.$type", $row['count'] );
			WikimediaStatsdExporter::sendNow( 'daily_wikidata_datamodel_property_datatype_total', $row['count'], [ 'datatype' => $type ] );
		}
	}

}
