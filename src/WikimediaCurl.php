<?php

/**
 * @author Adddshore
 *
 * Convenience function for pushing curl requests through the Wikimedia webproxy
 */
class WikimediaCurl {

	/**
	 * @param string $url
	 * @param bool $useWebProxy
	 *
	 * @return mixed
	 */
	public static function curlGet( $url, $useWebProxy = false ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		if ( $useWebProxy ) {
			curl_setopt( $ch, CURLOPT_PROXY, 'webproxy:8080' );
		}
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 0 );
		curl_setopt( $ch, CURLOPT_USERAGENT, "WMDE Wikidata metrics gathering" );
		$curl_scraped_page = curl_exec( $ch );
		curl_close( $ch );
		return $curl_scraped_page;
	}

	/**
	 * Retries an external get at most 9 times with an exponential back off
	 * The final retry wait period will be 640 seconds = 10 mins
	 * Max execution time would thus be 22 mins
	 *
	 * @param string $url
	 * @param bool $useWebProxy
	 *
	 * @return mixed
	 */
	public static function retryingCurlGet( $url, $useWebProxy = false ) {
		$retriesLeft = 7;
		$nextWait = 10;
		$result = false;

		while( $retriesLeft > 0 ) {
			$result = self::curlGet( $url, $useWebProxy );
			if( $result !== false && !empty( $result ) ) {
				return $result;
			}

			if( $result === false ) {
				trigger_error( "CURL failed - sleeping for $nextWait seconds: $url", E_USER_WARNING );
			} elseif( empty( $result ) ) {
				trigger_error( "CURL returned empty - sleeping for $nextWait seconds: $url", E_USER_WARNING );
			} else {
				throw new LogicException( "Retrying request for unknown reason: $url" );
			}

			sleep( $nextWait );
			$retriesLeft--;
			$nextWait = $nextWait * 2;
		}

		return $result;
	}

}
