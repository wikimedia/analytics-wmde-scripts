<?php

class Output {

	private $scriptName;

	/**
	 * @param string $scriptName
	 */
	public function __construct( $scriptName ) {
		$this->scriptName = $scriptName;
	}

	/**
	 * @param string $scriptName
	 *
	 * @return self
	 */
	public static function forScript( $scriptName ) {
		return new self( $scriptName );
	}

	public function markStart() {
		$this->outputMessage( 'Script Started!' );
		return $this;
	}

	public function markEnd() {
		$this->outputMessage( 'Script Finished!' );
		return $this;
	}

	public function dieWithMessage( $msg ) {
		$this->outputMessage( $msg );
		die();
	}

	public function outputMessage( $msg ) {
		echo date( "Y-m-d H:i:s" ) . ' ' . $this->scriptName . ' ' . $msg . "\n";
		return $this;
	}

}
