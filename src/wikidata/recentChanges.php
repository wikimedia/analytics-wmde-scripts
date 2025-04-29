#!/usr/bin/php
<?php

/**
 * @author Addshore
 * Gather data about the recent changes on Wikidata.org and send them ot graphite.
 * Used by: https://grafana.wikimedia.org/d/000000170/wikidata-edits
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikidata-recentChanges' )->markStart();
$metrics = new WikidataRc();
$metrics->execute();
$output->markEnd();

class WikidataRc {

	private $apiDateTime;
	private $graphiteDateTime;

	/**
	 * Set graphite and wm api times separately
	 */
	private function initDateTimes() {
		$defaultTimezone = date_default_timezone_get();

		// Mediawiki API / Wikidata expects it to be UTC
		date_default_timezone_set( 'UTC' );
		$this->apiDateTime = new DateTime( 'now - 1 minute' );

		date_default_timezone_set( $defaultTimezone );
		$this->graphiteDateTime = new DateTime( 'now - 1 minute' );
	}

	public function execute() {
		$this->initDateTimes();

		$data = [];
		$rccontinue = null;
		while ( true ) {
			$rawResponse = WikimediaCurl::curlGetExternal(
				$this->getUrl( $this->apiDateTime, $rccontinue )
			);
			if ( $rawResponse === false ) {
				throw new RuntimeException( 'Failed to get recent changes from API' );
			}
			$response = json_decode( $rawResponse[1], true );
			$data = array_merge( $data, $response['query']['recentchanges'] );
			if ( array_key_exists( 'continue', $response ) && array_key_exists( 'rccontinue', $response['continue'] ) ) {
				$rccontinue = $response['continue']['rccontinue'];
			} else {
				break;
			}
		}

		// Things defined here will be automatically sent to graphite
		// This can be at most 2 keys deep
		$counters = [
			'total' => 0,
			'bot' => 0,
			'anon' => 0,
			'new' => 0,
			'length' => 0,
			'mobile' => 0,
			'maxForAUser' => 0,
			'oauth' => [],
			'summary' => [],
		];

		$userEdits = [];

		foreach ( $data as $rc ) {
			$counters['total']++;
			$counters['length'] += ( $rc['newlen'] - $rc['oldlen'] );
			@$userEdits[$rc['user']]++;

			if ( array_key_exists( 'bot', $rc ) ) {
				$counters['bot']++;
			}

			if ( array_key_exists( 'anon', $rc ) ) {
				$counters['anon']++;
			}

			if ( array_key_exists( 'new', $rc ) ) {
				$counters['new']++;
			}

			foreach ( $rc['tags'] as $tag ) {
				if ( strpos( $tag, 'OAuth CID: ' ) === 0 ) {
					$oauth = substr( $tag, 11 );
					@$counters['oauth'][$oauth]++;
				}
				if ( $tag === 'mobile edit' ) {
					$counters['mobile']++;
				}
			}

			if ( strpos( $rc['comment'], '/* wb' ) === 0 ) {
				$end = min( strpos( $rc['comment'] . ':', ':' ), strpos( $rc['comment'] . '-', '-' ) );
				$summary = substr( $rc['comment'], 3, $end - 3 );
				@$counters['summary'][$summary]++;
			} else {
				@$counters['summary']['other']++;
			}

		}

		// Based on edits by user calculate the highest user edit rate
		foreach ( $userEdits as $user => $edits ) {
			if ( $edits >= $counters['maxForAUser'] ) {
				$counters['maxForAUser'] = $edits;
			}
		}

		// Send everything to graphite!
		$this->sendMetric( 'wikidata.rc.edits.total', $counters['total'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_all_total', $counters['total'] );
		$this->sendMetric( 'wikidata.rc.edits.bot', $counters['bot'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_bot_total', $counters['bot'] );
		$this->sendMetric( 'wikidata.rc.edits.anon', $counters['anon'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_anon_total', $counters['anon'] );
		$this->sendMetric( 'wikidata.rc.edits.new', $counters['new'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_new_total', $counters['new'] );
		$this->sendMetric( 'wikidata.rc.edits.length', $counters['length'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_length_total', $counters['length'] );
		$this->sendMetric( 'wikidata.rc.edits.mobile', $counters['mobile'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_mobile_total', $counters['mobile'] );
		$this->sendMetric( 'wikidata.rc.edits.maxForAUser', $counters['maxForAUser'], $this->graphiteDateTime );
		$this->sendMetricToPrometheus( 'wikidata_rc_edits_maxForAUser_total', $counters['maxForAUser'] );
		foreach ( $counters['oauth'] as $key => $value ) {
			$this->sendMetric( "wikidata.rc.edits.oauth.$key", $value, $this->graphiteDateTime );
			$this->sendMetricToPrometheus( 'wikidata_rc_edits_oauth_total', $value, [ 'key' => $key ] );
		}
		foreach ( $counters['summary'] as $key => $value ) {
			$this->sendMetric( "wikidata.rc.edits.summary.$key", $value, $this->graphiteDateTime );
			$this->sendMetricToPrometheus( 'wikidata_rc_edits_summary_total', $value, [ 'key' => $key ] );
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

		$url = 'https://www.wikidata.org/w/api.php?action=query&list=recentchanges&format=json';
		$url .= '&rcprop=comment|user|userid|tags|sizes|flags&rclimit=500&rctype=new|edit';
		$url .= "&rcstart=$rcStart&rcend=$rcEnd";
		if ( $rccontinue !== null ) {
			$url .= '&rccontinue=' . $rccontinue;
		}

		return $url;
	}

	private function sendMetric( $name, $value, DateTime $targetDate ) {
		$targetDate = $targetDate->format( 'Y-m-d H:i:s' );
		WikimediaGraphite::send( $name, $value, $targetDate );
	}

	private function sendMetricToPrometheus( $name, $value, $labels = [] ) {
		WikimediaStatsdExporter::sendNow( $name, $value, $labels );
	}

}
