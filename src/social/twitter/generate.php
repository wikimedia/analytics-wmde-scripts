<?php

libxml_use_internal_errors( true );
$metrics = new WikidataSocialMetric();
$metrics->execute();

class WikidataSocialMetric{

	public function execute() {
		$mysqlIni = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );
		$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet;dbname=staging", $mysqlIni['user'], $mysqlIni['password'] );

		$sql = 'INSERT INTO wikidata_social_twitter (date,followers) VALUES ';
		$sql .=
			'(' .
			$pdo->quote( date( "Y-m-d" ) ) . ', ' .
			$pdo->quote( $this->getTwitterFollowers() ) .
			');';

		echo "Writing SQL\n";
		$sqlResult = $pdo->exec( $sql );

		if( $sqlResult === false ){
			print_r($pdo->errorInfo());
		}

		echo "All done!";
	}

	/**
	 * Note: we could use the api but that requires authentication
	 * @return null|string
	 */
	private function getTwitterFollowers() {
		echo "Getting twitter followers\n";
		$dom = new DomDocument();
		$dom->loadHTML($this->curlGet( 'https://twitter.com/Wikidata' ));
		$xpath = new DomXPath($dom);
		$nodes = $xpath->query( '//a[@data-nav="followers"]/span[@class="ProfileNav-value"]' );
		if( $nodes->length !== 1 ) {
			return null;
		}
		return str_replace( ',', '', $nodes->item(0)->textContent );
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
