<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - pomocné funkce.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 */

namespace sbazar_crawler;


class Utils {
    /**
     * Slouží pro načtení šablony ze složky `partials`.
     * @param string $path
     * @param array $params (Optional.)
     * @param boolean $exit (Optional.)
     * @return void
     */
    public static function include_tpl( $path, array $params, $exit = false ) {
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
    public static function get_categories() {
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
            'Tablety a čtečky knih' => '815-tablety-ctecky-knih',
            'Zdraví a krása' => '28-zdravi-krasa',
            'Zvířata' => '90-zvirata',
        ];
    }

    /**
     * Vrátí pole s konfigurací pro Sbazar crawler. Načítá se ze souboru <code>config.php</code>.
     * @return void
     */
    public static function get_crawler_config() {
        return require_once( SC_PATH . 'config.php' );
    }

    /**
     * Vygeneruje nové Sbazar URL ze zadaných parametrů.
     * @param array $params
     * @return string
     * @todo Dokončit použití ostatních hodnot!
     */
    public static function get_current_url( array $params ) {
        $current_url = str_pad( $params['base_url'], 1, '/', STR_PAD_RIGHT );
        $price_from  = intval( $params['price_from'] );
        $price_to    = intval( $params['price_from'] );

        // Kategorie
        if( ! empty( $params['category'] ) ) {
            $current_url .= $params['category'];
        }

        // PSC
        if( ! empty( $params['town'] ) ) {
            $current_url .= '/' . $params['town'];
        } else {
            $current_url .= '/cela-cr';
        }

        // Cena od-do
        if( $price_from > 0 && $price_to <= 0 ) {// cena-od-5000-kc
            $current_url .= '/cena-od-' . $price_from . '-kc';
        }
        else if( $price_from <= 0 && $price_to > 0 ) {// cena-do-15000-kc
            $current_url .= '/cena-do-' . $price_to . '-kc';
        }
        else if( $price_from > 0 && $price_to > 0) {// cena-od-5000-do-15000-kc
            $current_url .= '/cena-od-' . $price_from . '-do-' . $price_to . '-kc';
        }

        return str_pad( $current_url, 1, '/', STR_PAD_RIGHT );
    }

    /**
     * Vrátí pole s konfigurací pro Sbazar crawler. Načítá se ze souboru <code>config.php</code>.
     * @param array $params
     * @return void
     */
    public static function set_crawler_config( array $params ) {
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
 * @package sbazar_crawler
 */

return [
    'base_url'    => %s,
    'category'    => %s,
    'price_from'  => %s,
    'price_to'    => %s,
    'town'        => %s,
    'sort'        => %s,
    'current_url' => %s,
    'channel'     => [
        'title'       => %s,
        'description' => %s,
        'language'    => 'cs',
    ],
];
PHP;
        if( empty( $params['base_url'] ) ) {
            $params['base_url'] = 'https://www.sbazar.cz/';
        }

        $current_url = self::get_current_url( $params );
        $php = sprintf(
                $tpl,
                date( 'j.n.Y H:i' ),
                '"' . $params['base_url'] . '"',
                empty( $params['category'] ) ? 'null' : '"' . $params['category'] . '"',
                empty( $params['price_from'] ) ? 'null' : ( int ) $params['price_from'],
                empty( $params['price_to'] ) ? 'null' : ( int ) $params['price_to'],
                empty( $params['town'] ) ? 'null' : '"' . $params['town'] . '"',
                empty( $params['sort'] ) ? 'null' : '"' . $params['sort'] . '"',
                '"' . $current_url . '"',
                '"' . $params['channel']['title'] . '"',
                '"' . $params['channel']['description'] . '"'
        );

        file_put_contents( SC_PATH . 'config.php', trim( $php ) );
    }

    /**
     * Zpracuje administrační formulář - pokud byl odeslán uloží hodnoty
     * do souboru <code>config.php</code>.
     * @return void
     */
    public static function process_admin_form() {
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
        /** @var array $channel Default parametrs for the output RSS channel. */
        $channel    = filter_input( INPUT_POST, 'sc_channel', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY );

        // Shromáždíme parametry
        $params = [
            'category'   => empty( $category ) ? null : $category,
            'price_from' => empty( $price_from ) ? null : $price_from,
            'price_to'   => empty( $price_to ) ? null : $price_to,
            'town'       => empty( $town ) ? null : $town,
            'sort'       => empty( $sort ) ? null : $sort,
            'channel'    => [
                'title'       => $channel['title'],
                'description' => $channel['description'],
                'language'    => 'cs',
            ],
        ];

        // A uložíme je
        self::set_crawler_config( $params );
    }

    /**
     * Vrátí hlavičku k RSS feedu.
     * @return string
     */
    public static function get_rss_feed_desc() {
        $config = include( 'config.php' );
        $channel = $config['channel'];

        $head = '';
        $head .= '<title>' . $channel['title'] . '</title>';
        $head .= '<description>' . $channel['description'] . '</description>';
        $head .= '<language>' . $channel['language'] . '</language>';
        $head .= '<link>' . SC_FEED_SELF_URL . '</link>';

        return $head;
    }


    /**
     * Vytvoří RSS soubor s inzeráty.
     * @param array $ads Pole s inzeráty (objekty typu {@see Ad}).
     * @return void
     */
    public static function set_rss_feed( array $ads = [] ) {
        $feed_head = self::get_rss_feed_desc();

        /**
         * @var string $rss Output RSS document (just plain text).
         */
        $rss = '';
        $rss .= '<?xml version="1.0" encoding="UTF-8"?>';
        $rss .= '<rss version="2.0">';
        $rss .= '<channel>';
        $rss .= $feed_head;

        foreach( $ads as $ad) {
            $rss .= $ad->to_rss_string();
        }

        $rss .= '</channel>';
        $rss .= '</rss>';

        // Zapíšeme do souboru
        file_put_contents( SC_RSS_FILE, $rss );
    }

    /**
     * @internal Escapes string to be XML-safe.
     * @param string $str
     * @return string
     */
    public static function xml_escape( $str ) {
        return str_replace( ['&', '<', '>', '\'', '"'], ['&amp;', '&lt;', '&gt;', '&apos;', '&quot;'], $str );
    }
}