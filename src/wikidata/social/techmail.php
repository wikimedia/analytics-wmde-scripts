#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of email accounts subscriber to the [wikidata-tech mailing list](https://lists.wikimedia.org/mailman/listinfo/wikidata-tech).
 * Mailman does not provide an api or a public count thus this metric needs to access the roster using a registered email and password.
 *
 * This script requires various bits of private infomation which are currently in the config file.
 * This file contains 1 value per line, the key and value are seperated with a space
 *
 * An example of this file would be:
 *
 * mm-wikidatatech-pass mailpass
 * mm-user user@domain.foo
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

require_once( __DIR__ . '/../../../lib/load.php' );
$output = Output::forScript( 'wikidata-social-techmail' )->markStart();
$metrics = new WikidataSocialMetric();
$metrics->execute();
$output->markEnd();

class WikidataSocialMetric{

	public function execute() {
		$value = $this->getMailingListSubscribers(
			'wikidata-tech',
			Config::getValue('mm-user'),
			Config::getValue('mm-wikidatatech-pass')
		);
		WikimediaGraphite::sendNow(
			'daily.wikidata.social.email.wikidata-tech.subscribers',
			$value
		);
	}

	private function getMailingListSubscribers( $listname, $mailmanuser, $mailmanpass ) {
		$vars = [
			'roster-email' => $mailmanuser,
			'roster-pw' => $mailmanpass,
			'language' => 'en',
		];
		$ch = WikimediaCurl::curlInit(
			'https://lists.wikimedia.org/mailman/roster/' . $listname,
			true
		);
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $vars);
		curl_setopt( $ch, CURLOPT_HEADER, 0);

		$response = curl_exec( $ch );

		if( preg_match( '/\<TITLE\>Error\<\/TITLE\>/i', $response ) ) {
			return null;
		}

		$data = [];
		preg_match( '/(\d+)\s+Non-digested/i', $response, $matches );
		$data['nondigest'] = array_pop( $matches );
		preg_match( '/(\d+)\s+Digested/i', $response, $matches );
		$data['digest'] = array_pop( $matches );
		preg_match_all( '/(\d+)\s+private members/i', $response, $matches );
		$data['private-nondigest'] = array_pop( $matches[1] );
		$data['private-digest'] = array_pop( $matches[1] );

		return array_sum( $data );
	}

}
