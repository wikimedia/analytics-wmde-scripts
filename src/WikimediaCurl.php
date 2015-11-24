<?php

/**
 * @author Adddshore
 *
 * Convenience function for pushing curl requests through the Wikimedia webproxy
 */
class WikimediaCurl {

	/**
	 * Retries an external get at most 9 times with an exponential back off
	 * The final retry wait period will be 640 seconds = 10 mins
	 * Max execution time would thus be 22 mins
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function retryingExternalCurlGet( $url ) {
		$retriesLeft = 7;
		$nextWait = 10;
		$result = false;

		while( $retriesLeft > 0 ) {
			$result = self::externalCurlGet( $url );
			if( $result !== false && !empty( $result ) ) {
				return $result;
			}

			if( $result === false ) {
				trigger_error( "curl request failed - sleeping for $nextWait seconds", E_USER_WARNING );
			} elseif( empty( $result ) ) {
				trigger_error( "curl request returned empty - sleeping for $nextWait seconds", E_USER_WARNING );
			} else {
				throw new LogicException( "Retrying request for unknown reason" );
			}

			sleep( $nextWait );
			$retriesLeft--;
			$nextWait = $nextWait * 2;
		}

		return $result;
	}

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
		return $curl_scraped_page;
	}

}
