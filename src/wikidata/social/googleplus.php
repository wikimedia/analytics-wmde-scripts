#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of followers the [Wikidata account](https://plus.google.com/105776413863749545202) has on google+.
 * This metric is generated using the googleplus api.
 *
 * This script requires various some private information which is currently in a config file.
 * This file contains 1 value per line, the key and value are separated with a space
 *
 * An example of this file would be:
 *
 * google somekeyhere
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-googleplus' )->markStart();
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getGooglePlusFollowers( Config::getValue('google') );
		WikimediaGraphite::sendNow( 'daily.wikidata.social.googleplus.followers', $value );
	}

	private function getGooglePlusFollowers( $googlePlusKey ) {
		$url = 'https://www.googleapis.com/plus/v1/people/105776413863749545202?key=' . $googlePlusKey;
		$response = WikimediaCurl::curlGetWithRetryExternal( $url );
		return json_decode($response[1])->{'circledByCount'};
	}

}
