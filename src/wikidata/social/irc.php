#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of users currently in the #wikidata irc channel on freenode.
 *
 * This metric probably heavily depends on the time that it is taken (currently once daily).
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-irc' )->markStart();
libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIrcChannelMembers();
		WikimediaGraphite::sendNow( 'daily.wikidata.social.irc.members', $value );
	}

	private function getIrcChannelMembers() {
		$dom = new DomDocument();
		$response = WikimediaCurl::retryingCurlGet( 'http://wm-bot.wmflabs.org/~wm-bot/db/systemdata.htm', true );
		$dom->loadHTML( $response[1] );
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//*[@id="H-wikidata"]/td[1]/span' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return $nodes->item(0)->textContent;
	}

}
