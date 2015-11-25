<?php

/**
 * @author Addshore
 */

require_once( __DIR__ . '/../../src/WikimediaDb.php' );
$metrics = new WikidataPropertiesByDataType();
$metrics->execute();

class WikidataPropertiesByDataType{

	public function execute() {
		$pdo = WikimediaDb::getPdo();
		$queryResult = $pdo->query( file_get_contents( __DIR__ . '/sql/properties_by_datatype.sql' ) );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		foreach( $rows as $row ) {
			$type = $row['type'];

			$this->sendMetric(
				"daily.wikidata.datamodel.property.datatype.$type",
				$row['count']
			);
		}
	}


	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
