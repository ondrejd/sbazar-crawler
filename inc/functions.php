<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - pomocné funkce.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */

namespace sbazar_crawler;


/**
 * Slouží pro načtení šablony ze složky `partials`.
 * @param string $path
 * @param array $params (Optional.)
 * @param boolean $exit (Optional.)
 * @return void
 */
function include_tpl( $path, array $params, $exit = false ) {
    extract( $params );
    ob_start( function() {} );
    include( $path );
    echo ob_get_flush();

    if( $exit === true ) {
        exit();
    }
}


/**
 * Vrátí kategorie Sbazaru.
 * @return array
 */
function get_categories() {
    return [
        'Auto-moto' => '1-auto-moto',
        'Dětský bazar' => '29-detsky-bazar',
        'Dům, byt a zahrada' => '8-dum-byt-zahrada',
        'Elektro a počítače' => '30-elektro-pocitace',
        'Hudba, knihy, hry a zábava' => '295-hudba-knihy-hry-zabava',
        'Nemovitosti' => '77-nemovitosti',
        'Oblečení, obuv a doplňky' => '15-obleceni-obuv-doplnky',
        'Služby' => '82-sluzby',
        'Sport' => '27-sport',
        'Starožitnosti, hobby a umění' => '33-starozitnosti-hobby-umeni',
        'Zdraví a krása' => '28-zdravi-krasa',
        'Zvířata' => '90-zvirata',
    ];
}


/**
 * Vrátí pole s konfigurací pro Sbazar crawler. Načítá se ze souboru 
 * <code>sbazar-crawler.config.php</code>.
 * @return void
 */
function get_crawler_config() {
    return require_once( SC_PATH . 'sbazar-crawler.config.php' );
}


/**
 * Vygeneruje nové Sbazar URL ze zadaných parametrů.
 * @param array $params
 * @return string
 */
function get_current_url( array $params ) {
    $current_url = str_pad( $params['base_url'], 1, '/', STR_PAD_RIGHT );

    if( ! empty( $params['category'] ) ) {
        $current_url = $current_url . $params['category'];
    }

    if( ! empty( $params['price_from'] ) ) {
        // ...
    }

    if( ! empty( $params['price_to'] ) ) {
        // ...
    }

    if( ! empty( $params['town'] ) ) {
        // ...
    }

    return str_pad( $current_url, 1, '/', STR_PAD_RIGHT );
}


/**
 * Vrátí pole s konfigurací pro Sbazar crawler. Načítá se ze souboru 
 * <code>sbazar-crawler.config.php</code>.
 * @param array $params
 * @return void
 */
function set_crawler_config( array $params ) {
    $tpl = <<<PHP
<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - nastavení crawleru.
 *
 * Tento soubor se automaticky ukládá po odeslání administračního formuláře,
 * takže není nutnost tento soubor editovat ručně.
 *
 * Vygenerováno: %s
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */

return [
    'base_url'    => %s,
    'category'    => %s,
    'price_from'  => %s,
    'price_to'    => %s,
    'town'        => %s,
    'sort'        => %s,
    'current_url' => %s,
];
PHP;

    $current_url = get_current_url( $params );
    $php = sprintf(
            $tpl,
            date( 'j.n.Y H:i' ),
            '"https://www.sbazar.cz/"',
            empty( $params['category'] ) ? 'null' : '"' . $params['category'] . '"',
            empty( $params['price_from'] ) ? 'null' : ( int ) $params['price_from'],
            empty( $params['price_to'] ) ? 'null' : ( int ) $params['price_to'],
            empty( $params['town'] ) ? 'null' : '"' . $params['town'] . '"',
            empty( $params['sort'] ) ? 'null' : '"' . $params['sort'] . '"',
            '"' . $current_url . '"'
    );

    file_put_contents( SC_PATH . 'sbazar-crawler.config.php', $php );
}


/**
 * Zpracuje administrační formulář - pokud byl odeslán uloží hodnoty
 * do souboru <code>sbazar-crawler.config.php</code>.
 * @return void
 */
function process_admin_form() {
    // Otestujeme jestli formulář byl odeslán
    if( filter_input( INPUT_POST, 'sc_submit' ) !== 'Uložit' ) {
        return;
    }

    // Shromáždíme odeslané hodnoty
    $category   = filter_input( INPUT_POST, 'sc_category' );
    $price_from = filter_input( INPUT_POST, 'sc_price_from' );
    $price_to   = filter_input( INPUT_POST, 'sc_price_to' );
    $town       = filter_input( INPUT_POST, 'sc_town' );
    $sort       = filter_input( INPUT_POST, 'sc_sort' );

    // Zajistíme ať jsou data konzistentní
    $params = array_merge( get_crawler_config(), [
        'category'   => empty( $category ) ? null : $category,
        'price_from' => empty( $price_from ) ? null : $price_from,
        'price_to'   => empty( $price_to ) ? null : $price_to,
        'town'       => empty( $town ) ? null : $town,
        'sort'       => empty( $sort ) ? null : $sort,
    ] );

    // Uložíme parametry crawleru
    set_crawler_config( $params );
}
