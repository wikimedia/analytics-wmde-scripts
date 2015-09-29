<?php

/**
 * This script requires various bits of private infomation which are currently in the config file.
 * This file contains 1 value per line, the key and value are seperated with a space
 *
 * An example of this file would be:
 *
 * google somekeyhere
 * mm-wikidata-pass mailpass1
 * mm-wikidatatech-pass mailpass2
 * mm-user user@domain.foo
 *
 * This script also requires a database table to write into!
 * //date,twitter,facebook,googleplus,identica,newsletter,mail,techmail,irc
 * CREATE TABLE IF NOT EXISTS wikidata_social ( date DATE NOT NULL, twitter INT(6), facebook INT(6), googleplus INT(6), identica INT(6), newsletter INT(6), mail INT(6), techmail INT(6), irc INT(6) );
 */

libxml_use_internal_errors(true);

/**
 * @return string[] service name keys and key values
 */
function getConfig() {
	$config = trim( file_get_contents( __DIR__ . '/../config' ) );
	$configLines = explode( "\n", $config );
	$config = array();
	foreach( $configLines as $configLine ) {
		$lineSplit = explode( ' ', trim( $configLine ), 2 );
		$config[$lineSplit[0]] = $lineSplit[1];
	}
	return $config;
}

$config = getConfig();

/**
 * Note: we could use the api but that requires authentication
 * @return null|string
 */
function getTwitterFollowers() {
	echo "Getting twitter followers\n";
	$dom = new DomDocument();
	$dom->loadHTML(curlGet( 'https://twitter.com/Wikidata' ));
	$xpath = new DomXPath($dom);
	$nodes = $xpath->query( '//a[@data-nav="followers"]/span[@class="ProfileNav-value"]' );
	if( $nodes->length !== 1 ) {
		return null;
	}
	return str_replace( ',', '', $nodes->item(0)->textContent );
}

function getFacebookLikes() {
	echo "Getting facebook likes\n";
	$url = 'http://m.facebook.com/wikidata';
	$response = curlGet($url);
	preg_match( '/([\d,]+) people like this/i', $response, $matches );
	return str_replace( ',', '', $matches[1] );
}

function getGooglePlusFollowers( $googlePlusKey ) {
	echo "Getting Google+ followers\n";
	$url = 'https://www.googleapis.com/plus/v1/people/105776413863749545202?key=' . $googlePlusKey;
	return json_decode(curlGet($url))->{'circledByCount'};
}

function getIdenticaFollowers() {
	echo "Getting identica followers\n";
	$dom = new DomDocument();
	$dom->loadHTML(curlGet( 'https://identi.ca/wikidata' ));
	$xpath = new DomXPath($dom);
	$nodes = $xpath->query( '//a[@href="/wikidata/followers"]/span[@class="label"]' );
	if( $nodes->length !== 1 ) {
		return null;
	}
	return $nodes->item(0)->textContent;
}

function getNewsletterSubscribers() {
	echo "Getting newsletter subscribers\n";
	$url = 'https://meta.wikimedia.org/wiki/Global_message_delivery/Targets/Wikidata?action=raw';
	$raw = curlGet( $url );
	return substr_count( $raw, '{{target' );
}

function getMailingListSubscribers( $listname, $mailmanuser, $mailmanpass ) {
	echo "Getting $listname mailing list subscribers\n";
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

function getIrcChannelMembers() {
	echo "Getting irc channel members\n";
	$data = curlGet( 'http://ircindexer.net/chan_stats1.php?network=freenode&channel=wikidata' );
	preg_match_all( '/\d+/', $data, $matches );
	return $matches[0][0];
}

function curlGet( $url ) {
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

$mysqlIni = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );
$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet;dbname=staging", $mysqlIni['user'], $mysqlIni['password'] );

$sql = 'INSERT INTO wikidata_social (date,twitter,facebook,googleplus,identica,newsletter,mail,techmail,irc) VALUES ';
$sql .=
	'(' .
	$pdo->quote( date( "Y-m-d" ) ) . ', ' .
	$pdo->quote( getTwitterFollowers() ) . ', ' .
	$pdo->quote( getFacebookLikes() ) . ', ' .
	$pdo->quote( getGooglePlusFollowers( $config['google'] ) ) . ', ' .
	$pdo->quote( getIdenticaFollowers() ) . ', ' .
	$pdo->quote( getNewsletterSubscribers() ) . ', ' .
	$pdo->quote( getMailingListSubscribers( 'wikidata', $config['mm-user'], $config['mm-wikidata-pass'] ) ) . ', ' .
	$pdo->quote( getMailingListSubscribers( 'wikidata-tech', $config['mm-user'], $config['mm-wikidatatech-pass'] ) ) . ', ' .
	$pdo->quote( getIrcChannelMembers() ) .
	');';

echo "Writing SQL\n";
$sqlResult = $pdo->exec( $sql );

if( $sqlResult === false ){
	print_r($pdo->errorInfo());
}

echo "All done!";