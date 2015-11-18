<?php

/**
 * @author Addshore
 *
 * This shows the number of likes the [Wikidata page](https://www.facebook.com/Wikidata) has on facebook.
 * This metric is generated using the mobile version of the facebook page (so that content exists there with javascript disabled).
 * The metric is then extracted using a regex.
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getFacebookLikes();
		exec( "echo \"daily.wikidata.social.facebook.likes $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getFacebookLikes() {
		$url = 'http://m.facebook.com/wikidata';
		$response = WikimediaCurl::externalCurlGet( $url );
		preg_match( '/([\d,]+) people like this/i', $response, $matches );
		return str_replace( ',', '', $matches[1] );
	}

}
