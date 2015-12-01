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
	 * @return array( header, body )|false
	 */
	public static function curlGet( $url, $useWebProxy = false ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		if ( $useWebProxy ) {
			curl_setopt( $ch, CURLOPT_PROXY, 'webproxy:8080' );
		}
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_USERAGENT, "WMDE Wikidata metrics gathering" );

		$response = curl_exec( $ch );
		if( $response === false ) {
			return false;
		}

		$header_size = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$header = substr( $response, 0, $header_size );
		$body = substr( $response, $header_size );

		curl_close( $ch );

		return array( $header, $body );
	}

	/**
	 * Retries an external get at most 9 times with an exponential back off
	 * The final retry wait period will be 640 seconds = 10 mins
	 * Max execution time would thus be 22 mins
	 *
	 * @param string $url
	 * @param bool $useWebProxy
	 *
	 * @return array( header, body )|false
	 */
	public static function retryingCurlGet( $url, $useWebProxy = false ) {
		$retriesLeft = 7;
		$nextWait = 10;
		$result = false;

		while( $retriesLeft > 0 ) {
			$result = self::curlGet( $url, $useWebProxy );
			if( $result !== false && !empty( $result[1] ) ) {
				return $result;
			}

			if( $result === false ) {
				trigger_error( "CURL request failed - sleeping for $nextWait seconds: $url", E_USER_WARNING );
			} elseif( empty( $result[1] ) ) {
				trigger_error( "CURL body returned empty - sleeping for $nextWait seconds: $url", E_USER_WARNING );
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
