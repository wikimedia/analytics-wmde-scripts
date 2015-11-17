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

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getIrcChannelMembers();
		exec( "echo \"daily.wikidata.social.irc.members $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getIrcChannelMembers() {
		$data = $this->curlGet( 'http://en.irc2go.com/webchat/?net=freenode&room=wikidata' );
		preg_match_all( '/(\d+) users/', $data, $matches );
		return $matches[1][0];
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
