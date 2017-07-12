<?php

/**
 * @author Adddshore
 */
class WikimediaDb {

	public static function getPdo() {
		// This config file is controlled by the statistics::wmde module
		$sqlConf = parse_ini_file( Config::getValue( 'db_file' ), false, INI_SCANNER_RAW );

		$pdo = new PDO(
			"mysql:host=" . Config::getValue( 'db_host' ),
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

}
