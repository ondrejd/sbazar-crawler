<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - pomocné funkce.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */

if( function_exists( 'sc_include_tpl' ) ) :
    /**
     * Slouží pro načtení šablony ze složky `partials`.
     * @param string $path
     * @param array $params (Optional.)
     * @param boolean $exit (Optional.)
     * @return void
     */
    function sc_include_tpl( $path, $params = [], $exit = false ) {
        extract( $params );
        ob_start( function() {} );
        include( $path );
        echo ob_get_flush();

        if( $exit === true ) {
            exit();
        }
    }
endif;

