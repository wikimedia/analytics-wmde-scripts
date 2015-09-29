<?php

$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$mysqlIni = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );
		$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet;dbname=staging", $mysqlIni['user'], $mysqlIni['password'] );

		$sql = 'INSERT INTO wikidata_social_facebook (date,likes) VALUES ';
		$sql .=
			'(' .
			$pdo->quote( date( "Y-m-d" ) ) . ', ' .
			$pdo->quote( $this->getFacebookLikes() ) .
			');';

		echo "Writing SQL\n";
		$sqlResult = $pdo->exec( $sql );

		if( $sqlResult === false ){
			print_r($pdo->errorInfo());
		}

		echo "All done!";
	}

	private function getFacebookLikes() {
		echo "Getting facebook likes\n";
		$url = 'http://m.facebook.com/wikidata';
		$response = $this->curlGet($url);
		preg_match( '/([\d,]+) people like this/i', $response, $matches );
		return str_replace( ',', '', $matches[1] );
	}

	private function curlGet( $url ) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_PROXY, 'webproxy:8080');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$curl_scraped_page = curl_exec($ch);
		curl_close($ch);
		return $curl_scraped_page;
	}

}
