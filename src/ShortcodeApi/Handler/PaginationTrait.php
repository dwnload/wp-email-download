<?php

namespace Dwnload\WpEmailDownload\ShortcodeApi\Handler;

/**
 * Class PaginationTrait
 *
 * @package Dwnload\WpEmailDownload\Classes\ShortcodeApi
 */
trait PaginationTrait {

    /**
     * Shortcode pagination.
     *
     * $paginate_links variable is used in the template
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     *
     * @param \WP_Query $wp_query Incoming query object
     *
     * @return string
     */
    protected function pagination( \WP_Query $wp_query ) {
        /**
         * For more options and info view the docs for paginate_links()
         * http://codex.wordpress.org/Function_Reference/paginate_links
         */
        $paginate_links = paginate_links( [
            'base' => str_replace( PHP_INT_MAX, '%#%', get_pagenum_link( PHP_INT_MAX ) ),
            'current' => max( 1, get_query_var( 'paged' ) ),
            'total' => $wp_query->max_num_pages,
            'mid_size' => 5,
            'prev_text' => __( '&laquo;', 'bb_wp_utilities' ),
            'next_text' => __( '&raquo;', 'bb_wp_utilities' ),
            'type' => 'list',
        ] );

        ob_start();
        include 'templates/pagination.php';
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
