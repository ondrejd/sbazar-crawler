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
 * Vytiskne RSS soubor s inzeráty.
 * @param boolean $exit (Optional.)
 * @return void
 */
function get_rss_feed( $exit = true ) {
    // ...

    if( $exit === true) {
        exit();
    }
}


/**
 * Vrátí parametry pro RSS kanál.
 * @return array
 */
function get_channel_params() {
    $channel = [
        'title' => '',
        'link' => '',
        'description' => '',
    ];
}


/**
 * Vytvoří RSS soubor s inzeráty.
 * @param array $ads Pole s inzeráty (objekty typu {@see Ad}).
 * @return void
 */
function set_rss_feed( array $ads = [] ) {
    $channel = get_channel_params();

    /**
     * @var string $rss Output RSS document (just plain text).
     */
    $rss = '';
    $rss .= <<<XML
<?xml version="1.0" encoding="UTF-8">
<rss version="2.0">
    <channel>
        <title>{$channel['title']}</title>
        <link>{$channel['link']}</link>
        <description>{$channel['description']}</description>
XML;

    foreach( $ads as $ad) {
        $item = <<<XML
        <item>
            <title>název inzerátu</title>
            <link>http://odkaz na detail inzerátu</link>
            <guid isPermaLink="true">http://www.d...</guid>
            <description><![CDATA[<img class="center" src="http://www.....cz/images/.jpg" alt=ta."/>jedna dvířka odšroubovaná, ale určitě se dají opravit a přidělat rozměry, cca 160 cm, š 120cm, h 37cm Simona, Brno, Jihomoravský ]]></description>
            <category> / Nábytek</category>
            <pubDate>Mon, 30 Jul 2017 09:41:33 +0000</pubDate>
        </item>
XML;
        $rss .= $item;
    }

    $rss .= <<<XML
    </channel>
</rss>
XML;
    // ...
    //file_put_contents( SC_PATH . 'feed.rss', $rss );
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
class AdsParser {
    /** @var array $ads */
    protected $ads = [];

    /** @var string $url */
    protected $url;

    /** @var \DOMDocument $doc */
    protected $doc;

    /**
     * ...
     * @return void
     */
    public function __construct() {
        // Z aktuální URL získáme HTML
        $this->url = get_crawler_config()['current_url'];
    }

    /**
     * Vrátí získané reklamy.
     * @return array
     */
    public function getAds() {
        return $this->ads;
    }

    /**
     * ...
     * @return void
     */
    public function parse() {
        // ...
$html = file_get_contents( $this->url );
// A vytvoříme z toho DOM dokument
$this->doc = new \DOMDocument();
$this->doc->loadHTML( $html, LIBXML_NOWARNING | LIBXML_ERR_NONE );

// Najdeme div obsahující všechny inzeráty
$div = $this->doc->getElementById( 'mrEggsResults' );
$first = $div->getElementsByTagName( 'div' )->item( 0 );

echo '<pre>';
//var_dump( $first );
//echo $first->ownerDocument->saveHTML( $first );
//exit();

$ads = $first->getElementsByTagName( 'div' );

// A projdeme je jeden po druhým
for( $i = 0; $i < $ads->length; $i++ ) {
    $ad_div = $ads->item( $i );
    if( ! $ad_div->hasAttribute( 'id' ) || ! $ad_div->hasAttribute( 'data-dot-data' ) ) {
        continue;
    }

    $id = $ad_div->getAttribute( 'id' );
    if( strpos( $id, 'inz-' ) !== 0 ) {
        continue;
    }

    $data = json_decode( $ad_div->getAttribute( 'data-dot-data' ));
    if( ! is_object( $data ) ) {
        continue;
    }

    $title = '';
    $spans = $ad_div->getElementsByTagName( 'span' );
    foreach( $spans as $span ) {
        if( $span->hasAttribute( 'class' ) && strstr( $div->getAttribute( 'class' ), 'descText' ) ) {
            $title = $span->textContent;
        }
    }

    //if( empty( $title ) ) {
    //    continue;
    //}

    $ad_obj = new Ad();
    $ad_obj->setId( $id );
    $ad_obj->setCategory( $data->categoryId );
    $ad_obj->setPrice( $data->price );
    $ad_obj->setTitle( $title );
    //$ad_obj->setLink( $link );
    //$ad_obj->setGuid( $guid );

var_dump($ad_obj);

}
exit();
//echo $this->url . PHP_EOL;
//var_dump( $div );

// ...
exit();
        // ...
    }
}
