#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Extracts data from api log files including the formats used, actions used and sends the data to graphite.
 *
 * @note Logrotate is at 6:25, + time for rsync (hourly?), 12 gives us roughly 6 hours
 * @note this is built for stat1002
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-api
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-apiLogScanner' )->markStart();

if ( array_key_exists( 1, $argv ) ) {
	$scanner = new WikidataApiLogScanner( $argv[1] );
} else {
	$scanner = new WikidataApiLogScanner();
}
$scanner->execute();
$output->markEnd();

class WikidataApiLogScanner {

	private $dayAfter;
	private $targetDate;

	private $formatWhitelist = [
		'dbg', 'dbgfm',
		'json', 'jsonfm',
		'php', 'phpfm',
		'raw', 'rawfm',
		'txt', 'txtfm',
		'xmlk', 'xmlfm',
		'yaml', 'yamlfm',
		'none',
	];

	/**
	 * @param string $targetDate must be parse-able by PHP
	 */
	public function __construct( $targetDate = 'yesterday -1 day' ) {
		$this->targetDate = new DateTime( $targetDate );
		$dayAfter = clone $this->targetDate;
		$dayAfter->modify( '+1 day' );
		$this->dayAfter = $dayAfter;
	}

	public function execute() {
		$this->dieIfFilesDoNotExist();

		$counters = [
			'formats' => [],
			'actions' => [],
		];

		$targetDate = $this->targetDate->format( 'Y-m-d' );
		foreach ( $this->getFilesNames() as $fileName ) {
			$handle = fopen( 'compress.zlib://' . $fileName, 'r' );
			if ( $handle === false ) {
				throw new RuntimeException( 'Failed to open file: ' . $fileName );
			}

			while ( ( $line = fgets( $handle ) ) !== false ) {
				if (
					// Log line should start with out target date
					strpos( $line, $targetDate ) !== 0 ||
					// And contain wikidatawiki
					strpos( $line, ' wikidatawiki ' ) === false
				) {
					continue;
				}

				// Extract the action (if set)
				if ( $actionStart = ( strpos( $line, ' action=' ) + 8 ) ) {
					$action = strtolower( substr( $line, $actionStart, strpos( $line, ' ', $actionStart ) - $actionStart ) );

					// Only count wikibase modules
					if ( preg_match( '/^wb\w+$/', $action ) ) {
						@$counters['actions'][$action]++;
					}

				}

				// Extract the format (if set)
				if ( $formatStart = ( strpos( $line, ' format=' ) + 8 ) ) {
					$format = strtolower( substr( $line, $formatStart, strpos( $line, ' ', $formatStart ) - $formatStart ) );
					@$counters['formats'][$format]++;
				}

			}
			fclose( $handle );
		}

		// Send everything to graphite!
		foreach ( $counters as $name => $counter ) {
			foreach ( $counter as $key => $value ) {
				if (
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
		$logDir = Config::getValue( 'api_log_dir' );
		return [
			$logDir . '/api.log-' . $this->targetDate->format( 'Ymd' ) . '.gz',
			$logDir . '/api.log-' . $this->dayAfter->format( 'Ymd' ) . '.gz',
		];
	}

	private function sendMetric( $name, $value ) {
		$targetDate = $this->targetDate->format( 'Y-m-d' );
		WikimediaGraphite::send( $name, $value, $targetDate );
	}

}
