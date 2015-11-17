<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://twitter.com/Wikidata) has on twitter.
 * This metric is generated using an xpath query on the twitter page.
 */

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
		$dom->loadHTML($this->curlGet( 'https://twitter.com/Wikidata' ));
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@data-nav="followers"]/span[@class="ProfileNav-value"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return str_replace( ',', '', $nodes->item(0)->textContent );
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
