<?php

/**
 * @author Adddshore
 */
class WikimediaDbList {

	public static function get( $list = 'all' ) {
		$dblist = WikimediaCurl::curlGet( 'https://noc.wikimedia.org/conf/dblists/' . $list . '.dblist' );
		if( $dblist === false ) {
			throw new RuntimeException( 'Failed to get db list ' . $list . '! (request failed)' );
		}
		// If the string has any html in it, we probably got an error page
		if( strstr( $dblist, '<' ) ) {
			throw new RuntimeException( 'Failed to get db list ' . $list . '! (html found)' );
		}
		$dbs = explode( "\n", $dblist[1] );
		$dbs = array_filter( $dbs );
		return $dbs;
	}

}
