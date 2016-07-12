<?php

/**
 * @author Adddshore
 */
class Config {

	/**
	 * @return array
	 */
	public static function getConfig() {
		$config = trim( file_get_contents( __DIR__ . '/../config' ) );
		$configLines = explode( "\n", $config );
		$configArray = array();
		foreach( $configLines as $configLine ) {
			$lineSplit = explode( ' ', trim( $configLine ), 2 );
			$configArray[$lineSplit[0]] = $lineSplit[1];
		}

		return $configArray;
	}

}
