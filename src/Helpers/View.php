<?php

namespace ArvaSeo\Helpers;

class View {

    /**
     * Render a view template from the /views directory.
     *
     * @param string $view   Dot-notation path to the view (e.g. 'admin.dashboard')
     * @param array  $data   Variables to extract and pass to the view
     *
     * @return null
     */
    public static function render( string $view, array $data = [] ): void {
        $file = self::resolve( $view );

        if ( ! file_exists( $file ) ) {
            wp_die( esc_html( "View not found: {$file}" ) );
        }

        extract( $data, EXTR_SKIP );

        require $file;
    }

    /**
     * Resolve the dot-notation view name to a full file path.
     *
     * @param string $view
     * @return string
     */
    private static function resolve( string $view ): string {
        $relative = str_replace( '.', DIRECTORY_SEPARATOR, $view ) . '.php';

        return ARVA_SEO_PATH . 'views' . DIRECTORY_SEPARATOR . $relative;
    }

}
