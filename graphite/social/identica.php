<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://identi.ca/wikidata) has on identica.
 * This metric is generated using an xpath query on the identica page.
 */

libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIdenticaFollowers();
		exec( "echo \"daily.wikidata.social.identica.followers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getIdenticaFollowers() {
		$dom = new DomDocument();
		$dom->loadHTML($this->curlGet( 'https://identi.ca/wikidata' ));
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@href="/wikidata/followers"]/span[@class="label"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return $nodes->item(0)->textContent;
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
