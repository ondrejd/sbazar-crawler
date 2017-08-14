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
 * @todo Dokončit použití ostatních hodnot!
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
    'channel'     => [
        'title'       => %s,
        'link'        => %s,
        'description' => %s,
        'language'    => 'cs',
    ],
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
            '"' . $current_url . '"',
            '"' . $params['channel']['title'] . '"',
            '"' . $params['channel']['link'] . '"',
            '"' . $params['channel']['description'] . '"'
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
    /** @var array $channel Default parametrs for the output RSS channel. */
    $channel    = filter_input( INPUT_POST, 'sc_channel', FILTER_DEFAULT , FILTER_REQUIRE_ARRAY );

    // Zajistíme ať jsou data konzistentní
    $params = [
        'category'   => empty( $category ) ? null : $category,
        'price_from' => empty( $price_from ) ? null : $price_from,
        'price_to'   => empty( $price_to ) ? null : $price_to,
        'town'       => empty( $town ) ? null : $town,
        'sort'       => empty( $sort ) ? null : $sort,
        'channel'    => [
            'title'       => empty( $channel['title'] ) ? '' : htmlentities( $channel['title'] ),
            'link'        => empty( $channel['link'] ) ? '' : $channel['link'],
            'description' => empty( $channel['description'] ) ? '' : htmlentities( $channel['description'] ),
            'language'    => 'cs',
        ],
    ];

    // Uložíme parametry crawleru
    set_crawler_config( $params );
}


/**
 * Vrátí parametry pro RSS kanál.
 * @return array
 */
function get_channel_params() {
    return get_crawler_config()['channel'];
}


/**
 * Vrátí hlavičku k RSS feedu.
 * @return string
 */
function get_rss_feed_desc() {
    $channel = get_channel_params();
    $head = '' .
            '<title>' . $channel['title'] . '</title>' . PHP_EOL .
            '<description>' . $channel['description'] . '</description>';

    if( ! empty( $channel['link'] ) ) {
        $head .= '<link>' . $channel['link'] . '</link>' . PHP_EOL;
    }

    $head .= '<language>' . $channel['language'] . '</language>' . PHP_EOL;

    return $head;
}


/**
 * Vytvoří RSS soubor s inzeráty.
 * @param array $ads Pole s inzeráty (objekty typu {@see Ad}).
 * @return void
 */
function set_rss_feed( array $ads = [] ) {
    $feed_head = get_rss_feed_desc();

    /**
     * @var string $rss Output RSS document (just plain text).
     */
    $rss = '';
    $rss .= <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
    <channel>
{$feed_head}
XML;

    foreach( $ads as $ad) {
        $rss .= $ad->to_rss_string();
    }

    $rss .= <<<XML
    </channel>
</rss>
XML;
    // Zapíšeme do souboru
    file_put_contents( SC_RSS_FILE, $rss );
}


/**
 * Pomocný objekt pro zachytávání chyb při parsování HTML.
 * @link https://stackoverflow.com/questions/1148928/disable-warnings-when-loading-non-well-formed-html-by-domdocument-php
 */
class ParserError {
    /**
     * @var callable $callback
     */
    protected $callback;

    /**
     * @var array $errors
     */
    protected $errors;

    /**
     * Constructor.
     * @param callable $callback
     * @return void
     */
    function __construct( $callback ) {
        $this->callback = $callback;
    }

    /**
     * Call the watched callback.
     * @return void
     */
    public function call() {
        $result = null;
        set_error_handler( [$this, 'on_error'] );

        try {
            $result = call_user_func( $this->callback, func_get_arg( 0 ) );
        } catch (Exception $ex) {
            restore_error_handler();        
            //throw $ex;
        }

        restore_error_handler();
        return $result;
    }

    /**
     * Called when error is occured.
     * @param string $errno
     * @param string $errstr
     * @param string $errfile
     * @param integer $errline
     * @return void
     */
    public function on_error( $errno, $errstr, $errfile, $errline ) {
        $this->errors[] = [$errno, $errstr, $errfile, $errline];
    }

    /**
     * @return boolean Returns TRUE if there were no errors.
     */
    public function ok() {
        return count( $this->errors ) <= 0;
    }

    /**
     * @return array Array with an errors.
     */
    public function errors() {
        return $this->errors;
    }
}


/**
 * Pomocný objekt představující jeden inzerát vytvořenou parserem.
 */
class Ad {
    /** @var integer $id */
    protected $id;
    /** @var integer $price */
    protected $price;
    /** @var integer $category */
    protected $category;
    /** @var string $title */
    protected $title;
    /** @var string $link */
    protected $link;
    /** @var string $guid */
    protected $guid;
    /** @var string $image_alt */
    protected $image_alt;
    /** @var string $image_src */
    protected $image_src;
    /** @var string $locality_lbl */
    protected $locality_lbl;
    /** @var string $locality_url */
    protected $locality_url;

    /**
     * @return integer Vrátí ID inzerátu.
     */
    public function get_id() {
        return $this->id;
    }

    /**
     * @param integer $id ID inzerátu.
     * @return void
     */
    public function set_id( $id ) {
        $this->id = $id;
    }

    /**
     * @return integer Vrátí cenu inzerátu.
     */
    public function get_price() {
        return $this->price;
    }

    /**
     * @param integer $price Cena inzerátu.
     * @return void
     */
    public function set_price( $price ) {
        $this->price = $price;
    }

    /**
     * @return integer Vrátí ID kategorie inzerátu.
     */
    public function get_category() {
        return $this->category;
    }

    /**
     * @param integer $category ID kategorie inzerátu.
     * @return void
     */
    public function set_category( $category ) {
        $this->category = $category;
    }

    /**
     * @return string Vrátí název inzerátu.
     */
    public function get_title() {
        return $this->title;
    }

    /**
     * @param string $title Název inzerátu.
     * @return void
     */
    public function set_title( $title ) {
        $this->title = $title;
    }

    /**
     * @return string Vrátí odkaz na inzerát.
     */
    public function get_link() {
        return $this->link;
    }

    /**
     * @param string $link Odkaz na inzerát.
     * @return void
     */
    public function set_link( $link ) {
        $this->link = $link;
    }

    /**
     * @return string Vrátí unikátní identifikátor inzerátu.
     */
    public function get_guid() {
        return $this->guid;
    }

    /**
     * @param string $guid Unikátní identifikátor inzerátu.
     * @return void
     */
    public function set_guid( $guid ) {
        $this->guid = $guid;
    }

    /**
     * @return string Vrátí popisek obrázku inzerátu.
     */
    public function get_image_alt() {
        return $this->image_alt;
    }

    /**
     * @param string $image_alt Popisek obrázku inzerátu.
     * @return void
     */
    public function set_image_alt( $image_alt ) {
        $this->image_alt = $image_alt;
    }

    /**
     * @return string Vrátí zdroj (URL) obrázku inzerátu.
     */
    public function get_image_src() {
        return $this->image_src;
    }

    /**
     * @param string $image_src Zdroj (URL) obrázku inzerátu.
     * @return void
     */
    public function set_image_src( $image_src ) {
        $this->image_src = $image_src;
    }

    /**
     * @return string Vrátí název lokality inzerátu.
     */
    public function get_locality_label() {
        return $this->locality_lbl;
    }

    /**
     * @param string $locality_lbl Název lokality inzerátu.
     * @return void
     */
    public function set_locality_label( $locality_lbl ) {
        $this->locality_lbl = $locality_lbl;
    }

    /**
     * @return string Vrátí část URL pro lokalitu inzerátu.
     */
    public function get_locality_url() {
        return $this->locality_url;
    }

    /**
     * @param string $locality_url Část URL pro lokalitu inzerátu.
     * @return void
     */
    public function set_locality_url( $locality_url ) {
        $this->locality_url = $locality_url;
    }

    /**
     * @return string Returns {@see AD} as the string with RSS <item> created from it.
     */
    public function to_rss_string() {
        $out = '';

        //<pubDate>Mon, 30 Jul 2017 09:41:33 +0000</pubDate>

        // Popisek
        $desc = $this->get_title();
        if( ! empty( $ad->get_price() ) ) {
            $desc .= '; Cena: ' . $this->get_price();
        }
        if( ! empty( $ad->get_locality_label() ) ) {
            $desc .= '; Lokalita: ' . $ad->get_locality_label();
        }

        // Obrázek
        $src = $ad->get_image_src();
        $mime = $this->get_img_mime_type( $src );

        // Vytvoříme výstupní XML
        $out .= '<item>';
        $out .= '<title>' . $this->get_title() . '</title>';
        $out .= '<description>' . $desc . '</description>';
        $out .= '<link>' . $this->get_link() . '</link>';
        $out .= '<guid isPermaLink="true">' . $this->get_link() . '</guid>';
        $out .= '<category>' . $this->get_category() . '</category>';

        if( ! empty( $src ) ) {
            $out .= '<enclosure url="' . $src . '" type="' . $mime . '"/>';
        }

        $out .= '</item>';

        return $out;
    }

    /**
     * @internal Returns MIME type for the image src string.
     * @param string $img
     * @return string
     */
    private function get_img_mime_type( $img ) {
        if( strpos( strtolower( $img ), '.jpg') > 0 || strpos( strtolower( $img ), '.jpeg') > 0 ) {
            return 'image/jpeg';
        }
        else if( strpos( strtolower( $img ), '.gif') > 0 ) {
            return 'image/gif';
        }
        else if( strpos( strtolower( $img ), '.png') > 0 ) {
            return 'image/png';
        }

        return null;
    }
}


/**
 * Samotný parser inzerátů z HTML stránek.
 */
class Crawler {
    /** @var array $ads */
    protected $ads = [];

    /** @var string $config */
    protected $base_url;

    /** @var string $url */
    protected $url;

    /** @var \DOMDocument $doc */
    protected $doc;

    /**
     * Aktuální stránka, která se parsuje.
     * @param integer $page
     */
    protected $page = 1;

    /**
     * Konstruktor.
     * @param array $config
     * @return void
     */
    public function __construct( $config ) {
        // Z aktuální URL získáme HTML
        $this->base_url = $config['base_url'];
        $this->url = $config['current_url'];
    }

    /**
     * Vrátí získané reklamy.
     * @return array
     */
    public function get_ads() {
        return $this->ads;
    }

    /**
     * Vrátí HTML pro parsovanou stránku jako čistý string.
     * @return string
     */
    protected function get_html() {
        $url = $this->url;
        if( $this->page > 1 ) {
            $url .= str_pad( $url, 1, '/', STR_PAD_RIGHT ) . $this->page;
        }

        return file_get_contents( $url );
    }

    /**
     * Vrátí TRUE pokud existuje další stránka pro parsování.
     * @return boolean
     */
    protected function has_next_page() {
        $anchor = $this->doc->getElementById( 'nextMrEggsLoader' );

        return ( $anchor instanceof \DOMElement );
    }

    /**
     * Započne s parsováním reklam z cílové URL.
     * @return void
     */
    public function parse() {
        $html = $this->get_html();
        // A vytvoříme z toho DOM dokument
        //$this->doc = new \DOMDocument();
        //$this->doc->loadHTML( $html, LIBXML_NOWARNING | LIBXML_ERR_NONE );
        $this->doc = new \DomDocument();
        // Chceme zamezit zbytečným PHP warningům při špatném HTML
        $caller = new ParserError( [$this->doc, 'loadHTML'] );
        $caller->call( $html );
        if ( ! $caller->ok() && SC_ENABLE_PARSER_LOG === true ) {
            ob_start();
            var_dump( $caller->errors() );
            $out = ob_get_clean();
            file_put_contents( SC_PATH . 'last.log', $out );
        }

        // Najdeme div obsahující všechny inzeráty
        $div = $this->doc->getElementById( 'mrEggsResults' );
        $first = $div->getElementsByTagName( 'div' )->item( 0 );
        $ads = $first->getElementsByTagName( 'div' );

        // A projdeme je jeden po druhým
        for( $i = 0; $i < $ads->length; $i++ ) {
            $ad_div = $ads->item( $i );
            if( ! $ad_div->hasAttribute( 'id' ) || ! $ad_div->hasAttribute( 'data-dot-data' ) ) {
                continue;
            }

            $ad_obj = $this->parse_ad( $ad_div );
            if( ( $ad_obj instanceof Ad )) {
                $this->ads[] = $ad_obj;
            }
        }

        if( $this->has_next_page() && $this->page < SC_MAX_PAGES_TO_PARSE ) {
            $this->page++;
            $this->parse();
        }
    }

    /**
     * @param \DOMElement $span
     * @return mixed Returns string (ad's title) or NULL.
     */
    protected function parse_ad_title( \DOMElement $span ) {
        // Pozn. kdyby jsme hledali třídu "descText", tak máme celý popis
        if( strstr( $span->getAttribute( 'class' ), 'title' ) ) {
            return trim( $span->textContent );
        }
        return null;
    }

    /**
     * @param \DOMElement $span
     * @return mixed Returns array (alt and src of the ad's image) or NULL.
     */
    protected function parse_ad_image( \DOMElement $span ) {
        if( ! SC_PARSE_AD_IMAGE ) {
            return null;
        }

        if( ! strstr( $span->getAttribute( 'class' ), 'image' ) ) {
            return null;
        }

        $img_elms = $span->getElementsByTagName( 'img' );
        if( $img_elms->length < 1 ) {
            return null;
        }

        $img_elm = $img_elms->item( 0 );
        if( ! ( $img_elm instanceof \DOMElement ) ) {
            return null;
        }

        $alt = $img_elm->getAttribute( 'alt' );
        $src = $img_elm->getAttribute( 'src' );

        if( empty( $src ) ) {
            return null;
        }

        return ['src' => $src, 'alt' => $alt];
    }

    /**
     * @param \DOMElement $span
     * @return mixed Returns array (label and URL part of the ad's locality) or NULL.
     */
    protected function parse_ad_locality( \DOMElement $span ) {
        if( ! $span->hasAttribute( 'data-locality-url-name' ) ) {
            return null;
        }

        return [
            'label' => $span->textContent,
            'url'   => $span->getAttribute( 'data-locality-url-name' ),
        ];
    }
    
    /**
     * Parsuje jeden div s inzeratem.
     * @param \DOMElement $ad_div
     * @return \sbazar_crawler\Ad|null
     */
    protected function parse_ad( \DOMElement $ad_div ) {
        // ID inzerátu
        $id = $ad_div->getAttribute( 'id' );
        if( strpos( $id, 'inz-' ) !== 0 ) {
            return;
        }

        // Data z atributu "data-dot-data"
        $data = json_decode( $ad_div->getAttribute( 'data-dot-data' ));
        if( ! is_object( $data ) ) {
            return;
        }

        // Název, obrázek lokalita inzerátu
        $title = null;
        $image = null;
        $locality = null;
        $spans = $ad_div->getElementsByTagName( 'span' );

        foreach( $spans as $span ) {
            if( ! $span->hasAttribute( 'class' ) ) {
                continue;
            }

            if( is_null( $title ) ) {
                $title = $this->parse_ad_title( $span );
            }

            if( is_null( $image ) ) {
                $image = $this->parse_ad_image( $span );
            }

            if( is_null( $locality ) ) {
                $locality = $this->parse_ad_locality( $span );
            }
        }

        if( empty( $title ) ) {
            return;
        }

        // Odkaz na inzerát
        $anchors = $ad_div->getElementsByTagName( 'a' );
        if( $anchors->length != 1 ) {
            return; // Pokud není končíme
        }
        else {
            $link = SC_SBAZAR_URL_PREFIX . $anchors->item( 0 )->getAttribute( 'href' );
        }

        // Nová reklama
        $ad_obj = new Ad();
        $ad_obj->set_id( $id );
        $ad_obj->set_category( $data->categoryId );
        $ad_obj->set_price( $data->price );
        $ad_obj->set_title( $title );
        $ad_obj->set_link( $link );
        //$ad_obj->set_guid( $guid );

        if( ! is_null( $image ) ) {
            $ad_obj->set_image_alt( $image['alt'] );
            $ad_obj->set_image_src( $image['src'] );
        }

        if( ! is_null( $locality ) ) {
            $ad_obj->set_locality_label( $locality['label'] );
            $ad_obj->set_locality_url( $locality['url'] );
        }

        return $ad_obj;
    }
}
