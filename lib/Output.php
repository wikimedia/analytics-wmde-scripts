<?php

class Output {

	/**
	 * @param string $script __FILE__
	 */
	public static function startScript( $script ) {
		self::timestampedMessage( 'START ' . $script );
	}

	/**
	 * @param string $string
	 */
	public static function timestampedMessage( $string ) {
		echo date( "Y-m-d H:i:s" ) . ' ' . $string . "\n";
	}

}
