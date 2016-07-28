#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://identi.ca/wikidata) has on identica.
 * This metric is generated using an xpath query on the identica page.
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-identica' )->markStart();
libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIdenticaFollowers();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.identica.followers', $value );
	}

	private function getIdenticaFollowers() {
		$url = 'https://identi.ca/wikidata';
		$dom = new DomDocument();
		$response = WikimediaCurl::retryingCurlGet( $url, true );
		$dom->loadHTML( $response[1] );
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@href="/wikidata/followers"]/span[@class="label"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return $nodes->item(0)->textContent;
	}

}
