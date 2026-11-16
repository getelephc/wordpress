<?php

// Native compile diagnostic, outside the verified application profiles.
function wordpress_json_media_cache_probe( string $media_type ): bool {
	static $cache = array();

	if ( ! isset( $cache[ $media_type ] ) ) {
		$cache[ $media_type ] = (bool) preg_match( '/(^|\s|,)application\/([\w!#\$&-\^\.\+]+\+)?json(\+oembed)?($|\s|;|,)/i', $media_type );
	}

	return (bool) $cache[ $media_type ];
}

echo json_encode(wordpress_json_media_cache_probe((string) ($argv[1] ?? 'application/json')));
