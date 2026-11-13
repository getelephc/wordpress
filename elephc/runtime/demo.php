<?php

function wordpress_demo_document(): string {
    return '<!-- wp:group {"layout":{"type":"constrained"}} --><main>'
        . '<!-- wp:heading --><h1>WordPress on Elephc</h1><!-- /wp:heading -->'
        . '<!-- wp:paragraph --><p>The real WordPress block parser, compiled to native code.</p><!-- /wp:paragraph -->'
        . '</main><!-- /wp:group -->';
}
