<?php

/**
 * @author Adddshore
 */
class Config {

	/**
	 * @var string[]|null
	 */
	private static $configArray = null;

	/**
	 * @return string[]
	 */
	private static function getConfig() {
		if ( self::$configArray === null ) {

			$fileContents = file_get_contents( __DIR__ . '/../../config' );
			if ( $fileContents === false ) {
				throw new Exception( 'Config file could not be read' );
			}
			$config = trim( $fileContents );
			$configLines = explode( "\n", $config );
			$configArray = [];
			foreach ( $configLines as $configLine ) {
				$lineSplit = explode( ' ', trim( $configLine ), 2 );
				$configArray[$lineSplit[0]] = $lineSplit[1];
			}
			self::$configArray = $configArray;
		}

		return self::$configArray;
	}

	/**
	 * @param string $key
	 *
	 * @return string
	 */
	public static function getValue( $key ) {
		$config = self::getConfig();
		if ( !array_key_exists( $key, $config ) ) {
			throw new Exception( 'Config value does not exist: ' . $key );
		}
		return $config[$key];
	}

}
