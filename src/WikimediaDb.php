<?php

/**
 * @author Adddshore
 */
class WikimediaDb {

	public static function getPdo() {
		// This script is controlled by the statistics::wmde module
		$sqlConf = parse_ini_file( '/etc/mysql/conf.d/research-wmde-client.cnf' );

		$pdo = new PDO(
			"mysql:host=analytics-store.eqiad.wmnet",
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

}
