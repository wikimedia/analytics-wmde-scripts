#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of likes the [Wikidata page](https://www.facebook.com/Wikidata) has on facebook.
 * This metric is generated using the mobile version of the facebook page (so that content exists there with javascript disabled).
 * The metric is then extracted using a simple regex from one of the meta tags, this could be done better.
 * <meta property="og:description" content="Wikidata, Berlin, Germany. 3,152 likes &#xb7; 52 talking about this. The free knowledge base that anyone can edit." />
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-facebook' )->markStart();
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getFacebookLikes();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.facebook.likes', $value );
	}

	private function getFacebookLikes() {
		$url = 'http://m.facebook.com/wikidata';
		$response = WikimediaCurl::curlGetWithRetryExternal( $url );
		preg_match( '/ ([\d,]+) likes /i', $response[1], $matches );
		return str_replace( ',', '', $matches[1] );
	}

}
