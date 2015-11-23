<?php

/**
 * @author Addshore
 */

$metrics = new WikidataPagesByNamespace();
$metrics->execute();

class WikidataPagesByNamespace{

	public function execute() {
		$sqlConf = parse_ini_file( '/etc/mysql/conf.d/analytics-research-client.cnf' );
		$pdo = new PDO( "mysql:host=analytics-store.eqiad.wmnet", $sqlConf['user'], $sqlConf['password'] );
		$queryResult = $pdo->query( $this->getSql() );

		if( $queryResult === false ) {
			throw new RuntimeException( "Something went wrong with the db query" );
		}

		$rows = $queryResult->fetchAll();

		$namespaceTotals = array( 0 => 0, 120 => 0 );
		foreach( $rows as $rowNumber => $row ) {
			$namespace = $row['namespace'];
			$type = $row['redirect'] == 1 ? 'redirects' : 'nonredirects';

			$this->sendMetric(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.$type",
				$row['count']
			);

			$namespaceTotals[$row['namespace']] += $row['count'];
		}

		foreach( $namespaceTotals as $namespace => $total ) {
			$this->sendMetric(
				"daily.wikidata.site_stats.pages_by_namespace.$namespace.total",
				$total
			);
		}
	}

	private function getSql() {
		return "SELECT page_namespace AS namespace, page_is_redirect AS redirect, count(*) AS count " .
			"FROM wikidatawiki.page " .
			"WHERE page_namespace = 0 OR page_namespace = 120 " .
			"GROUP BY page_namespace, page_is_redirect";
	}

	private function sendMetric( $name, $value ) {
		exec( "echo \"$name $value `date +%s`\" | nc -q0 graphite.eqiad.wmnet 2003" );
	}

}
