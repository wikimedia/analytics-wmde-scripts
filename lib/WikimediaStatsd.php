<?php

/**
 * @author Adddshore
 */
class WikimediaStatsd {

	public static function sendGauge( $metricName, $value ) {
		list( $host, $port ) = self::getHostAndPort();
		exec( "echo \"$metricName:$value|g\" | nc -w 1 -u $host $port" );
	}

	private static function getHostAndPort() {
		$host = Config::getValue( 'statsd_host' );
		$port = '8125';
		if ( strstr( $host, ':' ) ) {
			return explode( ':', $host );
		}
		return array( $host, $port );
	}

}
