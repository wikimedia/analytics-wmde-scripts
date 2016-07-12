#!/usr/bin/php
<?php

/**
 * @author Addshore
 *
 * This shows the number of email accounts subscriber to the [wikidata mailing list](https://lists.wikimedia.org/mailman/listinfo/wikidata).
 * Mailman does not provide an api or a public count thus this metric needs to access the roster using a registered email and password.
 *
 * This script requires various bits of private infomation which are currently in the config file.
 * This file contains 1 value per line, the key and value are seperated with a space
 *
 * An example of this file would be:
 *
 * mm-wikidata-pass mailpass1
 * mm-user user@domain.foo
 *
 * Used by: https://grafana.wikimedia.org/dashboard/db/wikidata-social-followers
 */

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$config = Config::getConfig();
		$value = $this->getMailingListSubscribers( 'wikidata', $config['mm-user'], $config['mm-wikidata-pass'] );
		exec( "echo \"daily.wikidata.social.email.wikidata.subscribers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	private function getMailingListSubscribers( $listname, $mailmanuser, $mailmanpass ) {
		$vars = array(
			'roster-email' => $mailmanuser,
			'roster-pw' => $mailmanpass,
			'language' => 'en',
		);
		$ch = curl_init( 'https://lists.wikimedia.org/mailman/roster/' . $listname );
		curl_setopt( $ch, CURLOPT_POST, 1);
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $vars);
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt( $ch, CURLOPT_HEADER, 0);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

		$response = curl_exec( $ch );

		if( preg_match( '/\<TITLE\>Error\<\/TITLE\>/i', $response ) ) {
			return null;
		}

		$data = array();
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
