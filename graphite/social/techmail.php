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
 */

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	/**
	 * @var array|null
	 */
	private $config = null;

	public function execute() {
		$config = $this->getConfig();
		$value = $this->getMailingListSubscribers( 'wikidata-tech', $config['mm-user'], $config['mm-wikidatatech-pass'] );
		exec( "echo \"daily.wikidata.social.email.wikidata-tech.subscribers $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

	/**
	 * @return string[] service name keys and key values
	 */
	private function getConfig() {
		if( $this->config === null ) {
			$config = trim( file_get_contents( __DIR__ . '/../../config' ) );
			$configLines = explode( "\n", $config );
			$config = array();
			foreach( $configLines as $configLine ) {
				$lineSplit = explode( ' ', trim( $configLine ), 2 );
				$config[$lineSplit[0]] = $lineSplit[1];
			}
			$this->config = $config;
		}
		return $this->config;
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
