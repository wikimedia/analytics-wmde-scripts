#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://twitter.com/Wikidata) has on twitter.
 * This metric is generated using an xpath query on the twitter page.
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-twitter' )->markStart();
libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getTwitterFollowers();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.twitter.followers', $value );
	}

	/**
	 * Note: we could use the api but that requires authentication
	 * @return null|string
	 */
	private function getTwitterFollowers() {
		$dom = new DomDocument();
		$url = 'https://twitter.com/Wikidata';
		$response = WikimediaCurl::curlGetWithRetryExternal( $url );
		$dom->loadHTML( $response[1] );
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@data-nav="followers"]/span[@class="ProfileNav-value"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return str_replace( ',', '', $nodes->item(0)->getAttribute('data-count') );
	}

}
