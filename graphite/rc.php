<?php

/**
 * @author Addshore
 */

$metrics = new WikidataRc();
$metrics->execute();

class WikidataRc {

	private $apiDateTime;
	private $graphiteDateTime;

	/**
	 * Set graphite and wm api times seperatly
	 */
	private function initDateTimes() {
		$defaultTimezone = date_default_timezone_get();

		// Mediawiki API / Wikidata expects it to be UTC
		date_default_timezone_set( "UTC" );
		$this->apiDateTime = new DateTime( 'now - 1 minute' );

		date_default_timezone_set( $defaultTimezone );
		$this->graphiteDateTime = new DateTime( 'now - 1 minute' );
	}

	public function execute() {
		$this->initDateTimes();

		$data = array();
		$rccontinue = null;
		while( true ) {
			$rawResponse = $this->file_get_contents( $this->getUrl( $this->apiDateTime, $rccontinue ) );
			if( $rawResponse === false ) {
				throw new RuntimeException( "Failed to get recent changes from API" );
			}
			$response = json_decode( $rawResponse, true );
			$data = array_merge( $data, $response['query']['recentchanges'] );
			if( array_key_exists( 'continue', $response ) && array_key_exists( 'rccontinue', $response['continue'] ) ) {
				$rccontinue = $response['continue']['rccontinue'];
			} else {
				break;
			}
		}

		$counters = array(
			'total' => 0,
			'bot' => 0,
			'length' => 0,
			'oauth' => array(),
			'summary' => array(),
		);

		foreach( $data as $rc ) {
			$counters['total']++;
			$counters['length'] += ( $rc['newlen'] - $rc['oldlen'] );

			if( array_key_exists( 'bot', $rc ) ) {
				$counters['bot']++;
			}

			foreach( $rc['tags'] as $tag ) {
				if( strpos( $tag, 'OAuth CID: ' ) === 0 ) {
					$oauth = substr( $tag, 11 );
					@$counters['oauth'][$oauth]++;
				}
			}

			if( strpos( $rc['comment'], '/* wb' ) === 0 ) {
				$end = min( strpos( $rc['comment'] . ':', ':' ), strpos( $rc['comment'] . '-', '-' ) );
				$summary = substr( $rc['comment'], 3, $end - 3 );
				@$counters['summary'][$summary]++;
			} else {
				@$counters['summary']['other']++;
			}
		}

		// Send everything to graphite!
		foreach( $counters as $name => $counter ) {
			if( is_array( $counter ) ) {
				foreach( $counter as $key => $value ) {
					$this->sendMetric( "wikidata.rc.edits.$name.$key", $value, $this->graphiteDateTime );
				}
			} else {
				$this->sendMetric( "wikidata.rc.edits.$name", $counter, $this->graphiteDateTime );
			}
		}

	}

	/**
	 * @param DateTime $forDateTime
	 * @param null|string $rccontinue
	 *
	 * @return string
	 */
	private function getUrl( DateTime $forDateTime, $rccontinue = null ) {
		$rcEnd = $forDateTime->format( 'YmdHi' ) . '00';
		$rcStart = $forDateTime->format( 'YmdHi' ) . '59';

		$url = "https://www.wikidata.org/w/api.php?action=query&list=recentchanges&format=json";
		$url .= "&rcprop=comment|tags|sizes|flags&rclimit=500&rctype=new|edit";
		$url .= "&rcstart=$rcStart&rcend=$rcEnd";
		if( $rccontinue !== null ) {
			$url .= '&rccontinue=' . $rccontinue;
		}

		return $url;
	}

	private function file_get_contents( $filename ) {
		$opts = array(
			'http' => array(
				'method' => "GET",
				'header' => "User-Agent: WMDE Wikidata metrics gathering\r\n",
			),
		);

		$context = stream_context_create( $opts );

		return file_get_contents( $filename, false, $context );
	}

	private function sendMetric( $name, $value, DateTime $targetDate ) {
		$targetDate = $targetDate->format( 'Y-m-d H:i:s' );
		exec(
			"echo \"$name $value `date -d \"$targetDate\" +%s`\" | nc -q0 graphite.eqiad.wmnet 2003"
		);
	}

}
