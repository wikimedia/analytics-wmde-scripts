<?php

/**
 * @author Addshore
 *
 * This shows the number of likes the [Wikidata page](https://www.facebook.com/Wikidata) has on facebook.
 * This metric is generated using the mobile version of the facebook page (so that content exists there with javascript disabled).
 * The metric is then extracted using a regex.
 */

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getFacebookLikes();
		exec( "echo \"daily.wikidata.social.facebook.likes $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getFacebookLikes() {
		$url = 'http://m.facebook.com/wikidata';
		$response = $this->curlGet($url);
		preg_match( '/([\d,]+) people like this/i', $response, $matches );
		return str_replace( ',', '', $matches[1] );
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
