<?php

/**
 * @author Addshore
 */
class WikimediaDbList {

	/**
	 * @param string $list
	 *
	 * @return string[]
	 */
	public static function get( $list = 'all' ) {
		$dblist = WikimediaCurl::curlGetExternal( 'https://noc.wikimedia.org/conf/dblists/' . $list . '.dblist' );
		if( $dblist === false ) {
			throw new RuntimeException( 'Failed to get db list ' . $list . '! (request failed)' );
		}
		// If the string has any html in it, we probably got an error page
		if( strstr( $dblist[1], '<' ) ) {
			throw new RuntimeException( 'Failed to get db list ' . $list . '! (html found)' );
		}
		$dbs = explode( "\n", $dblist[1] );
		$dbs = array_filter( $dbs );
		return $dbs;
	}

}
