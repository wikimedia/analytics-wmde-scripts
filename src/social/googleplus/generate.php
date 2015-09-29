<?php

/**
 * This script requires various some private information which is currently in a config file.
 * This file contains 1 value per line, the key and value are separated with a space
 *
 * An example of this file would be:
 *
 * google somekeyhere
 */

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	/**
	 * @var array|null
	 */
	private $config = null;

	public function execute() {
		$mysqlIni = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );
		$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet;dbname=staging", $mysqlIni['user'], $mysqlIni['password'] );

		$config = $this->getConfig();

		$sql = 'INSERT INTO wikidata_social_googleplus (date,followers) VALUES ';
		$sql .=
			'(' .
			$pdo->quote( date( "Y-m-d" ) ) . ', ' .
			$pdo->quote( $this->getGooglePlusFollowers( $config['google'] ) ) .
			');';

		echo "Writing SQL\n";
		$sqlResult = $pdo->exec( $sql );

		if( $sqlResult === false ){
			print_r($pdo->errorInfo());
		}

		echo "All done!";
	}

	/**
	 * @return string[] service name keys and key values
	 */
	private function getConfig() {
		if( $this->config === null ) {
			$config = trim( file_get_contents( __DIR__ . '/../../../config' ) );
			$configLines = explode( "\n", $config );
			$config = array();
			foreach( $configLines as $configLine ) {
				$lineSplit = explode( ' ', trim( $configLine ), 2 );
				$config[$lineSplit[0]] = $lineSplit[1];
			}
			$this->config = $config;
		}
		return $this->config;
	}

	private function getGooglePlusFollowers( $googlePlusKey ) {
		echo "Getting Google+ followers\n";
		$url = 'https://www.googleapis.com/plus/v1/people/105776413863749545202?key=' . $googlePlusKey;
		return json_decode($this->curlGet($url))->{'circledByCount'};
	}

	private function curlGet( $url ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_PROXY, 'webproxy:8080');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_scraped_page = curl_exec($ch);
		curl_close($ch);
		return $curl_scraped_page;
	}

}
