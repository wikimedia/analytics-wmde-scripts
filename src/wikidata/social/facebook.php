#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of likes the [Wikidata page](https://www.facebook.com/Wikidata) has on facebook.
 * This metric is generated using the mobile version of the facebook page (so that content exists there with javascript disabled).
 * The metric is then extracted using a regex.
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
Output::startScript( __FILE__ );

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getFacebookLikes();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.facebook.likes', $value );
	}

	private function getFacebookLikes() {
		$url = 'http://m.facebook.com/wikidata';
		$response = WikimediaCurl::retryingCurlGet( $url, true );
		preg_match( '/([\d,]+) people like this/i', $response[1], $matches );
		return str_replace( ',', '', $matches[1] );
	}

}
