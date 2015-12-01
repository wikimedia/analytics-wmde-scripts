#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://twitter.com/Wikidata) has on twitter.
 * This metric is generated using an xpath query on the twitter page.
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getTwitterFollowers();
		exec( "echo \"daily.wikidata.social.twitter.followers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	/**
	 * Note: we could use the api but that requires authentication
	 * @return null|string
	 */
	private function getTwitterFollowers() {
		$dom = new DomDocument();
		$url = 'https://twitter.com/Wikidata';
		$response = WikimediaCurl::retryingCurlGet( $url, true );
		$dom->loadHTML( $response[1] );
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@data-nav="followers"]/span[@class="ProfileNav-value"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return str_replace( ',', '', $nodes->item(0)->textContent );
	}

}
