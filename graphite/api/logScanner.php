#!/usr/bin/php
<?php

/**
 * Extracts data from API logs
 *
 * @author Addshore
 */

if ( array_key_exists( 1, $argv ) ) {
	$scanner = new WikidataApiLogScanner( $argv[1] );
} else {
	$scanner = new WikidataApiLogScanner();
}
$scanner->execute();

class WikidataApiLogScanner {

	private $dayAfter;
	private $targetDate;

	private $formatWhitelist = array(
		'dbg', 'dbgfm',
		'json', 'jsonfm',
		'php', 'phpfm',
		'raw', 'rawfm',
		'txt', 'txtfm',
		'xmlk', 'xmlfm',
		'yaml', 'yamlfm',
		'none',
	);

	/**
	 * @param string $targetDate must be parse-able by PHP
	 */
	public function __construct( $targetDate = 'yesterday' ) {
		$this->targetDate = new DateTime( $targetDate );
		$dayAfter = clone $this->targetDate;
		$dayAfter->modify( '+1 day' );
		$this->dayAfter = $dayAfter;
	}

	public function execute() {
		$this->dieIfFilesDoNotExist();

		$counters = array(
			'formats' => array(),
			'actions' => array(),
			'wbgetclaims.properties' => array(),
		);

		$targetDate = $this->targetDate->format( 'Y-m-d' );
		foreach ( $this->getFilesNames() as $fileName ) {
			$handle = fopen( 'compress.zlib://' . $fileName, 'r' );
			if ( $handle === false ) {
				throw new RuntimeException( 'Failed to open file: ' . $fileName );
			}

			while ( ( $line = fgets( $handle ) ) !== false ) {
				if(
					// Log line should start with out target date
					strpos( $line, $targetDate ) !== 0 ||
					// And contain wikidatawiki
					strpos( $line, ' wikidatawiki ' ) === false
				) {
					continue;
				}

				// Extract the action (if set)
				if( $actionStart = ( strpos( $line, ' action=' ) + 8 ) ) {
					$action = strtolower( substr( $line, $actionStart, strpos( $line, ' ', $actionStart ) - $actionStart ) );

					// Only count wikibase modules
					if( strpos( $action, 'wb' ) === 0 ) {
						@$counters['actions'][$action]++;
						if( $action === 'wbgetclaims' ) {

							// Extract the property (if set)
							if( $propertyStart = ( strpos( $line, ' property=' ) + 10 ) ) {
								$property = strtoupper( substr( $line, $propertyStart, strpos( $line, ' ', $propertyStart ) - $propertyStart ) );
								@$counters['wbgetclaims.properties'][$property]++;
							}

						}
					}

				}

				// Extract the format (if set)
				if( $formatStart = ( strpos( $line, ' format=' ) + 8 ) ) {
					$format = strtolower( substr( $line, $formatStart, strpos( $line, ' ', $formatStart ) - $formatStart ) );
					@$counters['formats'][$format]++;
				}

			}
			fclose( $handle );
		}

		// Send everything to graphite!
		foreach( $counters as $name => $counter ) {
			foreach( $counter as $key => $value ) {
				if(
					( $name == 'wbgetclaims.properties' && preg_match( '/P\d+/' ,$key ) ) ||
					( $name == 'formats' && !in_array( $key, $this->formatWhitelist ) ) ||
					strpos( $key, '_' ) !== false
				) {
					continue;
				}
				$this->sendMetric(
					'daily.wikidata.api.' . $name . '.' . $key,
					$value
				);
			}
		}
	}

	private function dieIfFilesDoNotExist() {
		foreach ( $this->getFilesNames() as $fileName ) {
			if ( !file_exists( $fileName ) ) {
				throw new RuntimeException( 'File does not exist: ' . $fileName );
			}
		}
	}

	private function getFilesNames() {
		return array(
			'/a/mw-log/archive/api/api.log-' . $this->targetDate->format( 'Ymd' ) . '.gz',
			'/a/mw-log/archive/api/api.log-' . $this->dayAfter->format( 'Ymd' ) . '.gz',
		);
	}

	private function sendMetric( $name, $value ) {
		$targetDate = $this->targetDate->format( 'Y-m-d' );
		exec(
			"echo \"$name $value `date -d \"$targetDate\" +%s`\" | nc -q0 graphite.eqiad.wmnet 2003"
		);
	}

}
