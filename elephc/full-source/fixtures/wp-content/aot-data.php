<?php

/** Finite loader for the pinned original core metadata and block-pattern sources. */
function aot_require_data( string $path ): array {
    switch ( $path ) {
        case ABSPATH . 'wp-includes/assets/script-modules-packages.php': return require ABSPATH . 'wp-includes/assets/script-modules-packages.php';
        case ABSPATH . 'wp-includes/assets/script-loader-packages.php': return require ABSPATH . 'wp-includes/assets/script-loader-packages.php';
        case ABSPATH . 'wp-includes/block-patterns/navigation-overlay-accent-bg.php': return require ABSPATH . 'wp-includes/block-patterns/navigation-overlay-accent-bg.php';
        case ABSPATH . 'wp-includes/block-patterns/navigation-overlay-black-bg.php': return require ABSPATH . 'wp-includes/block-patterns/navigation-overlay-black-bg.php';
        case ABSPATH . 'wp-includes/block-patterns/navigation-overlay-centered-with-extras.php': return require ABSPATH . 'wp-includes/block-patterns/navigation-overlay-centered-with-extras.php';
        case ABSPATH . 'wp-includes/block-patterns/navigation-overlay-centered.php': return require ABSPATH . 'wp-includes/block-patterns/navigation-overlay-centered.php';
        case ABSPATH . 'wp-includes/block-patterns/navigation-overlay.php': return require ABSPATH . 'wp-includes/block-patterns/navigation-overlay.php';
        case ABSPATH . 'wp-includes/block-patterns/query-grid-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-grid-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/query-large-title-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-large-title-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/query-medium-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-medium-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/query-offset-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-offset-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/query-small-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-small-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/query-standard-posts.php': return require ABSPATH . 'wp-includes/block-patterns/query-standard-posts.php';
        case ABSPATH . 'wp-includes/block-patterns/social-links-shared-background-color.php': return require ABSPATH . 'wp-includes/block-patterns/social-links-shared-background-color.php';
        case ABSPATH . 'wp-includes/blocks/blocks-json.php': return require ABSPATH . 'wp-includes/blocks/blocks-json.php';
        case ABSPATH . 'wp-includes/build/routes/connectors-home/content.min.asset.php': return require ABSPATH . 'wp-includes/build/routes/connectors-home/content.min.asset.php';
        case ABSPATH . 'wp-includes/build/routes/connectors-home/route.min.asset.php': return require ABSPATH . 'wp-includes/build/routes/connectors-home/route.min.asset.php';
        case ABSPATH . 'wp-includes/build/routes/font-list/content.min.asset.php': return require ABSPATH . 'wp-includes/build/routes/font-list/content.min.asset.php';
        case ABSPATH . 'wp-includes/build/routes/font-list/route.min.asset.php': return require ABSPATH . 'wp-includes/build/routes/font-list/route.min.asset.php';
        case ABSPATH . 'wp-includes/build/routes/fonts-home/route.min.asset.php': return require ABSPATH . 'wp-includes/build/routes/fonts-home/route.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/a11y/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/a11y/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/abilities/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/abilities/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-editor/utils/fit-text-frontend.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-editor/utils/fit-text-frontend.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/accordion/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/accordion/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/file/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/file/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/form/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/form/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/image/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/image/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/navigation/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/navigation/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/playlist/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/playlist/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/query/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/query/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/search/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/search/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/block-library/tabs/view.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/block-library/tabs/view.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/boot/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/boot/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/connectors/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/connectors/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/content-types/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/content-types/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/core-abilities/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/core-abilities/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/dashboard-init/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/dashboard-init/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/edit-site-init/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/edit-site-init/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/interactivity-router/full-page.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/interactivity-router/full-page.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/interactivity-router/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/interactivity-router/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/interactivity/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/interactivity/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/latex-to-mathml/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/latex-to-mathml/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/latex-to-mathml/loader.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/latex-to-mathml/loader.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/lazy-editor/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/lazy-editor/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/route/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/route/index.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/video-conversion/loader.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/video-conversion/loader.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/video-conversion/worker.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/video-conversion/worker.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/vips/loader.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/vips/loader.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/vips/worker.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/vips/worker.min.asset.php';
        case ABSPATH . 'wp-includes/js/dist/script-modules/workflow/index.min.asset.php': return require ABSPATH . 'wp-includes/js/dist/script-modules/workflow/index.min.asset.php';
        default: throw new RuntimeException( 'PHP data source is outside the finite manifest: ' . $path );
    }
}
