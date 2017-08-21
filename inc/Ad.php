<?php
/**
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 */

namespace sbazar_crawler;

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
    /** @var integer $image_length */
    protected $image_length;
    /** @var string $image_src */
    protected $image_src;
    /** @var string $image_type */
    protected $image_type;
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
     * @return integer Vrátí velikost obrázku inzerátu.
     */
    public function get_image_length() {
        return $this->image_length;
    }

    /**
     * @return string Vrátí zdroj (URL) obrázku inzerátu.
     */
    public function get_image_src() {
        return $this->image_src;
    }

    /**
     * @return string Vrátí typ obrázku inzerátu.
     */
    public function get_image_type() {
        return $this->image_type;
    }

    /**
     * @param integer $image_length Velikost obrázku inzerátu.
     * @return void
     */
    public function set_image_length( $image_length ) {
        $this->image_length = $image_length;
    }

    /**
     * @param string $image_src Zdroj (URL) obrázku inzerátu.
     * @return void
     */
    public function set_image_src( $image_src ) {
        $this->image_src = $image_src;
    }

    /**
     * @param string $image_type Typ obrázku inzerátu.
     * @return void
     */
    public function set_image_type( $image_type ) {
        $this->image_type = $image_type;
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
     * @return string Vrátí objekt {@see AD} jako řetězec s RSS <item>.
     * @todo Zahrnout správný <pubDate>!
     */
    public function to_rss_string() {
        $out = '';

        // Popisek
        $desc = $this->get_title();
        if( ! empty( $this->get_price() ) ) {
            $desc .= '; Cena: ' . $this->get_price();
        }
        if( ! empty( $this->get_locality_label() ) ) {
            $desc .= '; Lokalita: ' . $this->get_locality_label();
        }

        // TODO <pubDate>Mon, 30 Jul 2017 09:41:33 +0000</pubDate>
        $date = date( 'r' );

        // Obrázek
        $src = $this->get_image_src();

        // Vytvoříme výstupní XML
        $out .= '<item>';
        $out .= '<pubDate>' . $date . '</pubDate>';
        $out .= '<title>' . Utils::xml_escape( $this->get_title() ) . '</title>';
        $out .= '<description>' . Utils::xml_escape( $desc ) . '</description>';
        $out .= '<link>' . $this->get_link() . '</link>';
        //$out .= '<guid isPermaLink="true">' . $this->get_link() . '</guid>';
        $out .= '<category>' . $this->get_category() . '</category>';

        if( ! empty( $src ) ) {
            $out .= sprintf(
                    '<enclosure url="%s" type="%s" length="%s"/>',
                    $src,
                    $this->get_image_type(),
                    $this->get_image_length()
            );
        }

        $out .= '</item>';

        return $out;
    }
}