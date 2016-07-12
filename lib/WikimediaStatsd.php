<?php

/**
 * @author Adddshore
 */
class WikimediaStatsd {

	public static function sendGauge( $metricName, $value ) {
		$statsdHost = Config::getValue( 'statsd_host' );
		exec( "echo \"$metricName:$value|g\" | nc -w 1 -u $statsdHost 8125" );
	}

}
