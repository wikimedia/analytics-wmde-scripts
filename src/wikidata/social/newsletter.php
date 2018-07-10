#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of pages subscribed to the wikidata weekly summary post.
 * This metric is generated using a regex match on https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata.
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-newsletter' )->markStart();
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getNewsletterSubscribers();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.newsletter.subscribers', $value );
	}

	private function getNewsletterSubscribers() {
		$url = 'https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata?action=raw';
		$raw = WikimediaCurl::curlGetWithRetryExternal( $url );
		return substr_count( $raw[1], '{{target' );
	}

}
