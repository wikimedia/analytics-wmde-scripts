#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of pages subscribed to the wikidata weekly summary post.
 * This metric is generated using a regex match on https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata.
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getNewsletterSubscribers();
		exec( "echo \"daily.wikidata.social.newsletter.subscribers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getNewsletterSubscribers() {
		$url = 'https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata?action=raw';
		$raw = WikimediaCurl::retryingCurlGet( $url, true );
		return substr_count( $raw, '{{target' );
	}

}
