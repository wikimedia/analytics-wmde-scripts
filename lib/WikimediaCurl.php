<?php

/**
 * @author Addshore
 *
 * Convenience function for pushing curl requests through the Wikimedia webproxy
 */
class WikimediaCurl {

	/**
	 * This method should be used to request internal Wikimedia cluster resources
	 * For example wdqs1003.eqiad.wmnet
	 */
	public static function curlGetInternal( $url ) {
		return self::curlGet( $url, false );
	}

	/**
	 * These methods should be used to request any public or external URLs
	 * For example wikidata.org / query.wikidata.org / noc.wikimedia.org / twitter.com
	 */
	public static function curlGetExternal( $url ) {
		return self::curlGet( $url, true );
	}
	public static function curlGetWithRetryExternal( $url ) {
		return self::retryingCurlGet( $url, true );
	}

	/**
	 * @param $url string
	 * @param $useWebProxy bool
	 * @return resource a cURL handle
	 * @throws Exception if the config cant be loaded for some reason
	 */
	public static function curlInit( $url, $useWebProxy ) {
		$ch = curl_init();
		if ( $ch === false ) {
			throw new Exception( 'curl_init error' );
		}

		curl_setopt( $ch, CURLOPT_URL, $url );
		if ( $useWebProxy ) {
			curl_setopt( $ch, CURLOPT_PROXY, Config::getValue( 'web_proxy' ) );
		}

		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1 );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt( $ch, CURLOPT_HEADER, 1 );
		curl_setopt( $ch, CURLOPT_USERAGENT, "WMDE Wikidata metrics gathering" );

		return $ch;
	}

	/**
	 * @param string $url
	 * @param bool $useWebProxy
	 *
	 * @return array( array header, string body )|false
	 */
	protected static function curlGet( $url, $useWebProxy = false ) {
		$ch = self::curlInit( $url, $useWebProxy );

		$response = curl_exec( $ch );
		if( $response === false ) {
			return false;
		}

		$headerSize = curl_getinfo( $ch, CURLINFO_HEADER_SIZE );
		$headers = self::parseHeaders( substr( $response, 0, $headerSize ) );
		$body = substr( $response, $headerSize );

		curl_close( $ch );

		return array( $headers, $body );
	}

	/**
	 * Retries an external get at most 9 times with an exponential back off
	 * The final retry wait period will be 640 seconds = 10 mins
	 * Max execution time would thus be 22 mins
	 *
	 * @param string $url
	 * @param bool $useWebProxy
	 *
	 * @return array( array header, string body )|false
	 */
	protected static function retryingCurlGet( $url, $useWebProxy = false ) {
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

	/**
	 * @param string $headerText
	 * @return array
	 */
	private function parseHeaders( $headerText ) {
		$headers = array();
		foreach ( explode( "\r\n", $headerText ) as $i => $line ) {
			if ( $i === 0 ) {
				$headers['http_code'] = $line;
			} else {
				@list ( $key, $value ) = explode( ': ', $line );
				if ( !empty( $key ) ) {
					$headers[$key] = $value;
				}
			}
		}

		return $headers;
	}

}
