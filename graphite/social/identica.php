<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://identi.ca/wikidata) has on identica.
 * This metric is generated using an xpath query on the identica page.
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIdenticaFollowers();
		exec( "echo \"daily.wikidata.social.identica.followers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getIdenticaFollowers() {
		$url = 'https://identi.ca/wikidata';
		$dom = new DomDocument();
		$response = WikimediaCurl::retryingExternalCurlGet( $url );
		$dom->loadHTML( $response );
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@href="/wikidata/followers"]/span[@class="label"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return $nodes->item(0)->textContent;
	}

}
