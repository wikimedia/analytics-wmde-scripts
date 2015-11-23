<?php

/**
 * @author Adddshore
 */
class WikimediaDb {

	public static function getPdo() {
		$sqlConf = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );

		$pdo = new PDO(
			"mysql:host=analytics-store.eqiad.wmnet",
			$sqlConf['user'],
			$sqlConf['password']
		);

		return $pdo;
	}

}
