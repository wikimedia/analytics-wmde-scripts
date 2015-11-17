<?php

/**
 * @author Addshore
 *
 * This shows the number of pages subscribed to the wikidata weekly summary post.
 * This metric is generated using a regex match on https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata.
 */

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getNewsletterSubscribers();
		exec( "echo \"daily.wikidata.social.newsletter.subscribers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getNewsletterSubscribers() {
		$url = 'https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata?action=raw';
		$raw = $this->curlGet( $url );
		return substr_count( $raw, '{{target' );
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
