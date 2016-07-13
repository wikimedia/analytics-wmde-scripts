<?php

/**
 * @author Adddshore
 */
class Config {

	/**
	 * @var array|null
	 */
	private static $configArray = null;

	/**
	 * @return array
	 */
	public static function getConfig() {
		if ( self::$configArray === null ) {

			$config = trim( file_get_contents( __DIR__ . '/../../config' ) );
			$configLines = explode( "\n", $config );
			$configArray = array();
			foreach( $configLines as $configLine ) {
				$lineSplit = explode( ' ', trim( $configLine ), 2 );
				$configArray[$lineSplit[0]] = $lineSplit[1];
			}
			self::$configArray = $configArray;
		}

		return self::$configArray;
	}

	public static function getValue( $key ) {
		$config = self::getConfig();
		return $config[$key];
	}

}
