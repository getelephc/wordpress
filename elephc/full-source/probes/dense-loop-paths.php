<?php

// Dense branches must compile without enumerating every possible loop path.
function dense_loop_paths(int $rounds, bool $choice, int $exit): int {
    $total = 0;
    for ($i = 0; $i < $rounds; ++$i) {
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($choice) { $total += 1; } else { $total += 2; }
        if ($exit === 1) break;
        if ($exit === 2) continue;
        $total += 100;
    }
    return $total;
}
echo dense_loop_paths(0, false, 0), ':',
     dense_loop_paths(1, false, 0), ':',
     dense_loop_paths(1, true, 0), ':',
     dense_loop_paths(2, false, 0), ':',
     dense_loop_paths(2, true, 0), ':',
     dense_loop_paths(2, false, 1), ':',
     dense_loop_paths(2, true, 1), ':',
     dense_loop_paths(2, false, 2), ':',
     dense_loop_paths(2, true, 2);
