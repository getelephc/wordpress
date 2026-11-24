<?php

// Exercise the complete hook implementation after the real WordPress bootstrap.
$results = array();
foreach ( array( -10, 0, 10 ) as $trigger ) {
    foreach ( array( -20, -10, 0, 5, 10, 20 ) as $added ) {
        foreach ( array( null, -10, 0, 10 ) as $removed ) {
            foreach ( array( false, true ) as $recursive ) {
                $hook = new WP_Hook();
                $events = array();
                $callbacks = array();
                $changed = false;
                $depth = 0;
                foreach ( array( -10, 0, 10 ) as $priority ) {
                    $callbacks[ $priority ] = static function ( $value ) use (
                        $hook, &$events, &$callbacks, &$changed, &$depth,
                        $priority, $trigger, $added, $removed, $recursive
                    ) {
                        $events[] = array( $priority, $depth, $hook->current_priority() );
                        if ( $priority === $trigger && ! $changed ) {
                            $changed = true;
                            $hook->add_filter( 'cursor', static function ( $value ) use ( $hook, &$events, &$depth, $added ) {
                                $events[] = array( 'added', $added, $depth, $hook->current_priority() );
                                return $value . ':added';
                            }, $added, 1 );
                            if ( null !== $removed ) {
                                $hook->remove_filter( 'cursor', $callbacks[ $removed ], $removed );
                            }
                            if ( $recursive ) {
                                ++$depth;
                                $value = $hook->apply_filters( $value . ':nested', array( $value . ':nested' ) );
                                --$depth;
                            }
                        }
                        return $value . ':' . $priority;
                    };
                    $hook->add_filter( 'cursor', $callbacks[ $priority ], $priority, 1 );
                }
                $first = $hook->apply_filters( 'first', array( 'first' ) );
                $second = $hook->apply_filters( 'second', array( 'second' ) );
                $iteration = array();
                foreach ( $hook as $priority => $callbacks_at_priority ) {
                    $iteration[] = array( $priority, count( $callbacks_at_priority ) );
                }
                $results[] = array( $first, $second, $events, $hook->current_priority(), $iteration );
            }
        }
    }
}

$hook = new WP_Hook();
$hook->add_filter( 'direct', 'strtoupper', 5, 1 );
$hook->add_filter( 'direct', 'strtolower', 20, 1 );
$hook->rewind();
$results[] = array( $hook->key(), array_keys( $hook->current() ), array_keys( $hook->next() ), $hook->key() );
$results[] = array( $hook->next(), $hook->valid(), $hook->key() );
$hook->rewind();
$results[] = array( $hook->valid(), $hook->key(), array_keys( $hook->current() ) );

return $results;
