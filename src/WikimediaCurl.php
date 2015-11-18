<?php

/**
 * @author Adddshore
 *
 * Convenience function for pushing curl requests through the Wikimedia webproxy
 */
class WikimediaCurl {

	/**
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function externalCurlGet( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_PROXY, 'webproxy:8080' );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_USERAGENT, "WMDE Wikidata metrics gathering" );
		$curl_scraped_page = curl_exec( $ch );
		curl_close( $ch );
		if ( $curl_scraped_page === false ) {
			// TODO Throw exception?
		}

		return $curl_scraped_page;
	}

}
