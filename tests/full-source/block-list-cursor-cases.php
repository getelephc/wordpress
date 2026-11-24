<?php

$results = array();
$parsed = static function ( $id ) {
    return array(
        'blockName' => 'core/paragraph',
        'attrs' => array( 'probe_id' => $id ),
        'innerBlocks' => array(),
        'innerHTML' => '<p>cursor</p>',
        'innerContent' => array( '<p>cursor</p>' ),
    );
};
$snapshot = static function ( $list ) {
    $valid = $list->valid();
    $block = $valid ? $list->current() : null;
    return array(
        count( $list ), $list->key(), $valid,
        $block ? $block->parsed_block['attrs']['probe_id'] : null,
        $valid ? $list->current() === $block : null,
    );
};
foreach ( array( array(), array( 0, 1, 2 ), array( 3, 9, 20 ) ) as $keys ) {
    foreach ( array( 'next', 'remove-first', 'remove-current', 'remove-last', 'append', 'replace', 'rewind' ) as $first ) {
        foreach ( array( 'next', 'remove-first', 'remove-current', 'remove-last', 'append', 'replace', 'rewind' ) as $second ) {
            $blocks = array();
            foreach ( $keys as $key ) {
                $blocks[ $key ] = $parsed( 'initial-' . $key );
            }
            $list = new WP_Block_List( $blocks );
            $trace = array( $snapshot( $list ) );
            foreach ( array( $first, $second, 'next', 'append', 'next', 'next', 'append', 'rewind' ) as $index => $operation ) {
                if ( 'next' === $operation ) {
                    $list->next();
                } elseif ( 'rewind' === $operation ) {
                    $list->rewind();
                } elseif ( 'append' === $operation ) {
                    $list[] = $parsed( 'append-' . $index );
                } elseif ( 'replace' === $operation && $list->valid() ) {
                    $list[ $list->key() ] = $parsed( 'replace-' . $index );
                } elseif ( 'remove-current' === $operation && $list->valid() ) {
                    unset( $list[ $list->key() ] );
                } elseif ( 'remove-first' === $operation || 'remove-last' === $operation ) {
                    // Enumerate a clone so finding an offset leaves the tested cursor intact.
                    $copy = clone $list;
                    $offsets = array();
                    foreach ( $copy as $key => $block ) {
                        $offsets[] = $key;
                    }
                    if ( $offsets ) {
                        $offset = 'remove-first' === $operation ? $offsets[0] : $offsets[ count( $offsets ) - 1 ];
                        unset( $list[ $offset ] );
                    }
                }
                $trace[] = $snapshot( $list );
            }
            $results[] = $trace;
        }
    }
}

return $results;
