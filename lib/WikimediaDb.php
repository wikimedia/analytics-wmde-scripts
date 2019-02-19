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

	public static function getPdoNewHosts( $wiki, $mapper ) {
		// This config file is controlled by the statistics::wmde module
		$sqlConf = parse_ini_file( Config::getValue( 'db_file' ), false, INI_SCANNER_RAW );

		$section = $mapper->getSection( $wiki );

		$pdo = new PDO(
			"mysql:host=" . $section . Config::getValue( 'db_hosts_suffix' ),
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

	public static function getPdoStaging() {
		$sqlConf = parse_ini_file( Config::getValue( 'db_file' ), false, INI_SCANNER_RAW );
		$pdo = new PDO(
			"mysql:host=" . $section . Config::getValue( 'db_staging_host' ),
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

}
