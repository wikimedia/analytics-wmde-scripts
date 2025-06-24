<?php

/**
 * @author Amir Sarabadani
 */
class WikimediaDbSectionMapper {

	/**
	 * @var string[]|null
	 */
	private $dbMap;

	/**
	 * @param string[]|null $dbMap
	 */
	public function __construct( array $dbMap = null ) {
		$this->dbMap = $dbMap;
	}

	/**
	 * @param string $db
	 *
	 * @return string[]
	 */
	public function getSection( $db = 'wikidatawiki' ) {
		if ( $this->dbMap === null ) {
			$this->loadDbMap();
		}

		if ( !array_key_exists( $db, $this->dbMap ) ) {
			// Default section
			$section = 's3';
		} else {
			$section = $this->dbMap[$db];
		}

		$port = $this->getPortFromSection( $section );
		return [ 'section' => $section, 'port' => $port ];
	}

	private function loadDbMap() {
		$this->dbMap = [];
		$allByName = array_flip( WikimediaDbList::get( 'all' ) );
		$sections = [ 's1', 's2', 's3', 's4', 's5', 's6', 's7', 's8', 's11' ];
		foreach ( $sections as $section ) {
			$wikis = WikimediaDbList::get( $section );
			foreach ( $wikis as $wiki ) {
				$this->dbMap[$wiki] = $section;
				unset( $allByName[$wiki] );
			}
		}
		if ( $allByName !== [] ) {
			throw new RuntimeException(
				'Error: We tried to get all wikis from ' . implode( ',', $sections ) .
				', but the following all.dblist wikis were not found: ' . implode( ',', array_keys( $allByName ) )
			);
		}
	}

	/**
	 * @param string $section
	 *
	 * @return string
	 */
	private function getPortFromSection( $section ) {
		return '331' . substr( $section, -1 );
	}

}
