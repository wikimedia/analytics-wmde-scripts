<?php

class WikimediaStatsdExporter {
	public static function sendNow( $metricName, $value, $labels = [] ) {
		list( $host, $port ) = self::getHostAndPort();
		$line = $metricName . ':' . $value . '|' . self::getMetricType( $metricName );
		if ( $labels !== [] ) {
			$allLabels = array_map(
				[ self::class, 'encodeLabel' ], // TODO self::encodeLabel(...) in PHP 8
				array_keys( $labels ),
				$labels
			);
			$line .= '|#' . implode( ',', $allLabels );
		}
		exec( "echo '$line' | nc -u -w 1 $host $port" );
	}

	private static function getMetricType( $metricName ): string {
		// There is no support for other types now.
		// TODO use str_ends_with( $metricName, '_seconds' ) in PHP 8
		if ( substr( $metricName, -8 ) === '_seconds' ) {
			return 'ms';
		}
		return 'c';
	}

	private static function encodeLabel( string $label, $value ) {
		$value = (string)$value;
		// TODO use str_contains() in PHP 8
		if ( strpos( $value, "'" ) !== false || strpos( $value, '\\' ) !== false || strpos( $value, ',' ) !== false ) {
			// if needed, we could implement "\""-style escaping later, but throw for now
			throw new RuntimeException( 'Unsupported label value: ' . $value );
		}
		if ( strpos( $label . $value, "'" ) !== false ) {
			throw new RuntimeException( 'Unsupported label name: ' . $label );
		}
		return "{$label}:{$value}";
	}

	/**
	 * @return string[]
	 */
	private static function getHostAndPort() {
		$host = Config::getValue( 'statsd_exporter_host' );
		$port = Config::getValue( 'statsd_exporter_port' );
		if ( strstr( $host, ':' ) ) {
			return explode( ':', $host, 2 );
		}
		return [ $host, $port ];
	}
}
