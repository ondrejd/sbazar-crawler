<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar.
 *
 * Fungování skriptu:
 *
 * V zásadě má skript tři funkce:
 *
 * 1) zobrazit stránku s administrací (nastavení parametrů, info o posledním CRON jobu)
 * 2) vygenerovat RSS (což by se mělo dít v rámci CRON jobu a pak jen 24 hodin nabízet ke stažení)
 * 3) zobrazit dané RSS
 *
 * Nastavení parametrů v administraci:
 *
 * 1) je možno nastavit kategorii
 * 2) je možno nastavit cenovou hladinu (od - do(?))
 *
 * Skript je po nastavení parametrů nutno také nastavit v CRON úlohách pro daný
 * server tak, aby se každý den v určený čas spouštěl a generoval nové RSS.
 * Spouštěcí cyklus jde samozřejmě případně nastavit i kratší.
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 *
 * @todo Přesunout HTML/XML do /partials/*.phtml|*.pxml
 * @todo Zlepšit kód (včetně komentářů)
 */

namespace sbazar_crawler;

// Cesta ke zdrojovým souborům
defined( 'SC_PATH' ) || define( 'SC_PATH', dirname( __FILE__ ) . '/' );

// Jméno výstupního souboru s feedem.
defined( 'SC_RSS_FILE' ) || define( 'SC_RSS_FILE', SC_PATH . 'feed.rss' );

// Aktuálně platné heslo do administrace
defined( 'SC_ADMIN_PASS' ) || define( 'SC_ADMIN_PASS', 'fuzeWPSPFx3duEt4' );

// Aktuální URL k feedu.
defined( 'SC_FEED_SELF_URL' ) || define( 'SC_FEED_SELF_URL', 'http://localhost:7777/' );
//defined( 'SC_FEED_SELF_URL' ) || define( 'SC_FEED_SELF_URL', 'http://83.cz/sbazar-crawler/' );

// Maximulní doba pro zpuštění (600 s = 10 min)
defined( 'SC_MAX_EXEC_TIME' ) || define( 'SC_MAX_EXEC_TIME', 3200 );

// Maximální počet stránek Sbazaru na parsování (spíše pro vývoj)
defined( 'SC_MAX_PAGES_TO_PARSE' ) || define( 'SC_MAX_PAGES_TO_PARSE', 999999 );

// Vytvářet soubor s chybama při parsování HTML?
defined( 'SC_ENABLE_PARSER_LOG' ) || define( 'SC_ENABLE_PARSER_LOG', false );

// Prefix URL Sbazaru
defined( 'SC_SBAZAR_URL_PREFIX' ) || define( 'SC_SBAZAR_URL_PREFIX', 'https://www.sbazar.cz' );

// Pokud je TRUE, pak jsou parsovány i obrázky inzerátů
defined( 'SC_PARSE_AD_IMAGE' ) || define( 'SC_PARSE_AD_IMAGE', true );

// Ostatní zdrojáky
include_once( SC_PATH . 'inc/Ad.php' );
include_once( SC_PATH . 'inc/Crawler.php' );
include_once( SC_PATH . 'inc/ParserError.php' );
include_once( SC_PATH . 'inc/Utils.php' );

// Musíme taky zvýšit časový limit
ini_set( 'max_execution_time', SC_MAX_EXEC_TIME );

/**
 * TRUE pokud chceme spustit CRON úlohu pro stažení dat z SBazaru a přípravy nového RSS souboru.
 * @var boolean $is_cron_job
 */
$is_cron_job = empty( filter_input( INPUT_GET, 'cron' ) ) ? false : true;

/**
 * TRUE pokud chceme spustit administraci.
 * @var boolean $is_admin
 */
$is_admin = empty( filter_input( INPUT_GET, 'admin' ) ) ? false : true;

/**
 * Zadané heslo do administrace
 * @var string $password
 */
$admin_pass = filter_input( INPUT_GET, 'admin' );

// Něco jako jednoduchý controller :)
if( $is_admin === true && $admin_pass != SC_ADMIN_PASS ) {
    // Vyžádána administrace, ale se špatným heslem
    header( 'Content-Type: text/html;charset=UTF-8 ' );
    Utils::include_tpl( SC_PATH . 'partials/admin-wrong_pass.phtml', [], true );
    exit();
}
if( $is_admin === true ) {
    // Administrace
    // Zpracujeme formulář
    Utils::process_admin_form();
    // Připravíme si parametry pro šablonu
    $params = Utils::get_crawler_config();

    // Nevím proč, ale po uložení konfiguračního souboru, dochází k chybě,
    // že není vráceno pole, ale boolean, toto je řešení:
    if( ! is_array( $params ) ) {
        $protocol = ( ! empty( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] !== 'off' ) ? 'https://' : 'http://';
        $self_url = $protocol .  $_SERVER['HTTP_HOST'] . '/' . basename( __FILE__ ) . '?admin=' . SC_ADMIN_PASS;
        header( "Location: {$self_url}" );
    }
    
    // Zobrazíme adminstraci
    header( 'Content-Type: text/html;charset=UTF-8 ' );
    Utils::include_tpl( SC_PATH . 'partials/admin.phtml', $params, true );
    exit();
}
elseif( $is_cron_job === true ) {
    // Spustíme parsování Sbazaru pro nový RSS
    header( 'Content-Type: text/plain;charset=UTF-8 ' );

    echo 'CRON job is executed!' . PHP_EOL;

    // Inicializujeme parser
    $parser = new Crawler( Utils::get_crawler_config() );
    // A začneme parsovat HTML
    $parser->parse();
    // Nakonec musíme vše uložit jako nový RSS feed
    Utils::set_rss_feed( $parser->get_ads() );

    echo 'CRON job is finished!';
    exit();
}
else {
    // Zobrazíme RSS ostatním návštěvníkům
    header( 'Content-Type: application/rss+xml; charset=utf-8' );

    // Pokud RSS soubor existuje vypíšeme ho a konec
    if( file_exists( SC_RSS_FILE ) && is_readable( SC_RSS_FILE ) ) {
        $rss = file_get_contents( SC_RSS_FILE );
        echo $rss;
        exit();
    }

    // Jinak vypíšeme prázdný feed
?>
<rss version="2.0">
    <channel>
<?php echo Utils::get_rss_feed_desc() ?>
    </channel>
</rss>
<?php
}
