<?php

/**
 * @author Adddshore
 */
class WikimediaGraphite {

	public static function send( $metricName, $value, $date ) {
		$graphiteHost = Config::getValue( 'graphite_host' );
		exec( "echo \"$metricName $value `date -d \"$date\" +%s`\" | nc -q0 $graphiteHost 2003" );
	}

	public static function sendNow( $metricName, $value ) {
		$graphiteHost = Config::getValue( 'graphite_host' );
		exec( "echo \"$metricName $value `date +%s`\" | nc -q0 $graphiteHost 2003" );
	}

}
