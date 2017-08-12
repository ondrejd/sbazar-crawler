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


/**
 * Vrátí parametry pro RSS kanál.
 * @return array
 */
function get_channel_params() {
    $params = [
        'title' => 'Feed',
        'link' => 'http://localhost:7777/sbazar-crawler.php',
        'description' => 'Popisek feedu.',
    ];
    return $params;
}


/**
 * Vrátí hlavičku k RSS feedu.
 * @return string
 */
function get_rss_feed_desc() {
    $channel = get_channel_params();
    return '' .
            '<title>' . $channel['title'] . '</title>' . PHP_EOL .
            '<link>' . $channel['link'] . '</link>' . PHP_EOL .
            '<description>' . $channel['description'] . '</description>';
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
//<pubDate>Mon, 30 Jul 2017 09:41:33 +0000</pubDate>
        $desc = $ad->getTitle() . ' - ' . $ad->getPrice();
        $item = <<<XML

        <item>
            <title>{$ad->getTitle()}</title>
            <description>{$desc}</description>
            <link>{$ad->getLink()}</link>
            <guid isPermaLink="true">{$ad->getLink()}</guid>
            <category>{$ad->getCategory()}</category>
        </item>
XML;
        $rss .= $item;
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
    protected $callback;
    protected $errors;
    function __construct( $callback ) {
        $this->callback = $callback;
    }
    function call() {
        $result = null;
        set_error_handler( [$this, 'onError'] );

        try {
            $result = call_user_func( $this->callback, func_get_arg( 0 ) );
        } catch (Exception $ex) {
            restore_error_handler();        
            //throw $ex;
        }

        restore_error_handler();
        return $result;
    }
    function onError( $errno, $errstr, $errfile, $errline ) {
        $this->errors[] = [$errno, $errstr, $errfile, $errline];
    }
    function ok() {
        return count( $this->errors ) <= 0;
    }
    function errors() {
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

    /**
     * @return integer Vrátí ID inzerátu.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * @param integer $id ID inzerátu.
     * @return void
     */
    public function setId( $id ) {
        $this->id = $id;
    }

    /**
     * @return integer Vrátí cenu inzerátu.
     */
    public function getPrice() {
        return $this->price;
    }

    /**
     * @param integer $price Cena inzerátu.
     * @return void
     */
    public function setPrice( $price ) {
        $this->price = $price;
    }

    /**
     * @return integer Vrátí ID kategorie inzerátu.
     */
    public function getCategory() {
        return $this->category;
    }

    /**
     * @param integer $category ID kategorie inzerátu.
     * @return void
     */
    public function setCategory( $category ) {
        $this->category = $category;
    }

    /**
     * @return string Vrátí název inzerátu.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @param string $title Název inzerátu.
     * @return void
     */
    public function setTitle( $title ) {
        $this->title = $title;
    }

    /**
     * @return string Vrátí odkaz na inzerát.
     */
    public function getLink() {
        return $this->link;
    }

    /**
     * @param string $link Odkaz na inzerát.
     * @return void
     */
    public function setLink( $link ) {
        $this->link = $link;
    }

    /**
     * @return string Vrátí unikátní identifikátor inzerátu.
     */
    public function getGuid() {
        return $this->guid;
    }

    /**
     * @param string $guid Unikátní identifikátor inzerátu.
     * @return void
     */
    public function setGuid( $guid ) {
        $this->guid = $guid;
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
     * Parsuje jeden div s inzeratem.
     * @param \DOMElement $ad_div
     * @return \sbazar_crawler\Ad|null
     * @todo Pokud bude třeba obrázek tak viz. {link https://cyber.harvard.edu/rss/rss.html#ltenclosuregtSubelementOfLtitemgt}
     */
    protected function parse_ad( \DOMElement $ad_div ) {
        $id = $ad_div->getAttribute( 'id' );
        if( strpos( $id, 'inz-' ) !== 0 ) {
            return;
        }

        $data = json_decode( $ad_div->getAttribute( 'data-dot-data' ));
        if( ! is_object( $data ) ) {
            return;
        }

        $title = '';
        $spans = $ad_div->getElementsByTagName( 'span' );

        foreach( $spans as $span ) {
            if( $span->hasAttribute( 'class' ) && strstr( $span->getAttribute( 'class' ), 'title' ) ) {
                // Pozn. kdyby jsme hledali třídu "descText", tak máme celý popis
                $title = trim( $span->textContent );
            }
        }

        if( empty( $title ) ) {
            return;
        }

        $anchors = $ad_div->getElementsByTagName( 'a' );
        if( $anchors->length != 1 ) {
            return;
        }

        $link = 'https://www.sbazar.cz' . $anchors->item( 0 )->getAttribute( 'href' );

        $ad_obj = new Ad();
        $ad_obj->setId( $id );
        $ad_obj->setCategory( $data->categoryId );
        $ad_obj->setPrice( $data->price );
        $ad_obj->setTitle( $title );
        $ad_obj->setLink( $link );
        //$ad_obj->setGuid( $guid );

        return $ad_obj;
    }
}
