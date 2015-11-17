<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://plus.google.com/105776413863749545202) has on google+.
 * This metric is generated using the googleplus api.
 *
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
		$config = $this->getConfig();
		$value = $this->getGooglePlusFollowers( $config['google'] );
		exec( "echo \"daily.wikidata.social.googleplus.followers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	/**
	 * @return string[] service name keys and key values
	 */
	private function getConfig() {
		if( $this->config === null ) {
			$config = trim( file_get_contents( __DIR__ . '/../../config' ) );
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
