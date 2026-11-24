<?php

final class WordPressArrayBoundaryProbe {
    public array $values;

    public function __construct( array $values ) {
        $this->values = $values;
    }
}

$inputs = array(
    array(), array( null ), array( false ), array( 0 ), array( '' ),
    array( 'first', 'last' ), array( 3 => 'sparse', 50 => null ),
    array( 'name' => array( 'nested' => true ), -3 => array( 1, 2 ) ),
    array( 1, false, null, 'WordPress 🐘' ),
);
$results = array();
foreach ( $inputs as $values ) {
    $probe = new WordPressArrayBoundaryProbe( (array) $values );
    $first = function_exists( 'wp_aot_array_first' ) ? wp_aot_array_first( $probe->values ) : reset( $values );
    $first_key = function_exists( 'wp_aot_array_first_key' ) ? wp_aot_array_first_key( $probe->values ) : key( $values );
    $last = function_exists( 'wp_aot_array_last' ) ? wp_aot_array_last( $probe->values ) : end( $values );
    $results[] = array( $first, $last, $first_key, $probe->values );
}
echo json_encode( $results );
