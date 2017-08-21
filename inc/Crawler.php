<?php
/**
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 */

namespace sbazar_crawler;

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
        if( ! ( $div instanceof \DOMElement ) ) {
            return;
        }

        $first = $div->getElementsByTagName( 'div' )->item( 0 );
        if( ! ( $first instanceof \DOMElement ) ) {
            return;
        }

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
        $src = 'https:' . $img_elm->getAttribute( 'data-origin' );
        $img = get_headers( $src, 1 );
        $ret = [
            'src'    => $src,
            'alt'    => trim( $alt ),
            'type'   => is_array( $img ) ? $img['Content-Type'] : null,
            'length' => is_array( $img ) ? $img['Content-Length'] : null,
        ];

        if( empty( $ret['src'] ) || is_null( $ret['length'] ) || is_null( $ret['type'] ) ) {
            return null;
        }

        return $ret;
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
            $ad_obj->set_image_type( $image['type'] );
            $ad_obj->set_image_length( $image['length'] );
        }

        if( ! is_null( $locality ) ) {
            $ad_obj->set_locality_label( $locality['label'] );
            $ad_obj->set_locality_url( $locality['url'] );
        }

        return $ad_obj;
    }
}