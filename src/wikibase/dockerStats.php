#!/usr/bin/php
<?php
/**
 *
 * @author Addshore
 * Track stats related to the wikibase docker images
 * Used by: https://grafana.wikimedia.org/d/000000516/wikibase-docker-images
 */

require_once __DIR__ . '/../../lib/load.php';
$output = Output::forScript( 'wikibase-dockerStats' )->markStart();
$metrics = new WikibaseDockerStats( $output );
$metrics->execute(
	[
		[ 'wikibase', 'wikibase' ],
		[ 'wikibase', 'wdqs' ],
		[ 'wikibase', 'wdqs-frontend' ],
		[ 'wikibase', 'wdqs-proxy' ],
	]
);
$output->markEnd();

class WikibaseDockerStats {

	private $out;

	public function __construct( Output $out ) {
		$this->out = $out;
	}

	public function execute( array $imageList ) {
		foreach ( $imageList as $imageData ) {
			list( $orgName, $imageName ) = $imageData;
			$this->executeForImage( $orgName, $imageName );
		}
	}

	public function executeForImage( $org, $image ) {
		$this->out->outputMessage( 'Running for: ' . $org . '/' . $image );
		$rawResponse = WikimediaCurl::curlGetExternal( $this->getUrl( $org, $image ) );
		if ( $rawResponse === false ) {
			throw new RuntimeException( 'Failed to get data from API' );
		}
		$response = json_decode( $rawResponse[1], true );
		$pullCount = $response['pull_count'];

		$metricName = 'daily.wikibase.dockerImage.pulls.' . $image;
		$this->out->outputMessage( 'Sending data for: ' . $org . '/' . $image );
		WikimediaGraphite::sendNow( $metricName, $pullCount );
	}

	/**
	 * @param string $repo
	 * @param string $image
	 * @return string
	 */
	private function getUrl( $repo, $image ) {
		return 'https://hub.docker.com/v2/repositories/' . $repo . '/' . $image;
	}

}
