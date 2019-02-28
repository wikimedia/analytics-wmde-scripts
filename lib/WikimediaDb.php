<?php

/**
 * @author Adddshore
 */
class WikimediaDb {

	/**
	 * @return PDO
	 */
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

		$hostData = $mapper->getSection( $wiki );
		$section = $hostData['section'];
		$port = $hostData['port'];

		$pdo = new PDO(
			"mysql:host=" . $section . Config::getValue( 'db_hosts_suffix' ) .
			";port=" . (string)$port,
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

	/**
	 * @return PDO
	 */
	public static function getPdoStaging() {
		$sqlConf = parse_ini_file( Config::getValue( 'db_file' ), false, INI_SCANNER_RAW );
		$pdo = new PDO(
			"mysql:host=" . Config::getValue( 'db_staging_host' ) .
			';port=' . Config::getValue( 'db_staging_port' ),
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

	public static function buildInsertSql( $table, $columns, $values ) {
		if ( $values ==  [] ) {
			return null;
		}

		$sql = "INSERT IGNORE INTO $table ( $columns ) VALUES ";
		foreach ( $values as $row ) {
			$sql .= '(';
			foreach ( $row as $datum ) {
				$datum = json_encode( $datum );
				$sql .= "${datum},";
			}
			$sql = substr( $sql, 0, -1 ) . '),';
		}

		return substr( $sql, 0, -1 ) . ';';
	}

}
