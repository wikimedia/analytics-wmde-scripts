#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of users currently in the #wikidata irc channel on freenode.
 * This metric is generated using a regex match on the irc2go.com service.
 *
 * Once https://phabricator.wikimedia.org/T115247 is resolved we could use Wm-bot!
 *
 * If this service stops working there are alternatives.
 * Another alternative would be to make the script actually join the channel to perform the check.
 *
 * This metric probably heavily depends on the time that it is taken (currently once daily).
 */

require_once( __DIR__ . '/../../src/WikimediaCurl.php' );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIrcChannelMembers();
		exec( "echo \"daily.wikidata.social.irc.members $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getIrcChannelMembers() {
		$data = WikimediaCurl::retryingCurlGet( 'http://en.irc2go.com/webchat/?net=freenode&room=wikidata', true );
		preg_match_all( '/(\d+) users/', $data[1], $matches );
		return $matches[1][0];
	}

}
