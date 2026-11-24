<?php

$results = array();
$inputs = array(
    array(),
    array( 'Content-Type' => 'text/html', 'X-WordPress' => 'native' ),
    array( 'X-Case' => 'first', 'x-case' => 'second' ),
    array( 'X-Null' => null, 'X-False' => false, 'X-Empty' => '' ),
    array( 'X-Unicode' => 'WordPress 🐘', 'X-List' => array( 'first', 'second' ) ),
    array( 3 => 7, 'X-Number' => 12.5 ),
);
foreach ( $inputs as $input ) {
    $headers = new WpOrg\Requests\Utility\CaseInsensitiveDictionary( (array) $input );
    $results[] = $headers->getAll();
    $results[] = array( $headers['CONTENT-TYPE'], $headers['x-CASE'], isset( $headers['X-NULL'] ), isset( $headers['x-false'] ) );
    $headers['X-New'] = 'added';
    $headers['x-NEW'] = 'replaced';
    unset( $headers['CONTENT-TYPE'] );
    $iteration = array();
    foreach ( $headers as $name => $value ) {
        $iteration[] = array( $name, $value );
    }
    $results[] = $iteration;
    $results[] = $headers->getAll();
    try {
        $headers[] = 'invalid append';
        $results[] = array( 'unexpected success' );
    } catch ( WpOrg\Requests\Exception $error ) {
        $results[] = array( $error->getMessage(), $error->getType(), $error->getData(), $error->getCode() );
    }
}
echo json_encode( $results );
