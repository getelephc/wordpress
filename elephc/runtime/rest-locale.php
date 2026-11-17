<?php

function wordpress_scalar_plural(string $singular, string $plural, mixed $count): string {
    return $count === 1 ? $singular : $plural;
}
