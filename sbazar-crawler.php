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
 *
 * @todo Přesunout HTML/XML do /partials/*.phtml|*.pxml
 * @todo Zlepšit kód (včetně komentářů)
 */

namespace sbazar_crawler;

// Cesta ke zdrojovým souborům
defined( 'SC_PATH' ) || define( 'SC_PATH', dirname( __FILE__ ) . '/' );

// Aktuálně platné heslo do administrace
defined( 'SC_ADMIN_PASS' ) || define( 'SC_ADMIN_PASS', 'fuzeWPSPFx3duEt4' );

// Ostatní zdrojáky
require_once( SC_PATH . 'inc/functions.php' );

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
    include_tpl( SC_PATH . 'partials/admin-wrong_pass.phtml', [], true );
    exit();
}
if( $is_admin === true ) {
    // Administrace
    // Zpracujeme formulář
    process_admin_form();
    // Připravíme si parametry pro šablonu
    $params = get_crawler_config();
    
    // Zobrazíme adminstraci
    header( 'Content-Type: text/html;charset=UTF-8 ' );
    include_tpl( SC_PATH . 'partials/admin.phtml', $params, true );
    exit();
}
elseif( $is_cron_job === true ) {
    // Spustíme parsování Sbazaru pro nový RSS
    
    echo 'CRON job should be executed!'.PHP_EOL;
    
    // ...
    exit();
}
else {
    // Zobrazíme RSS ostatním návštěvníkům
    //header('Content-Type: application/xml');
    header('Content-Type: application/rss+xml; charset=utf-8');
    echo '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
?>
<!-- Example RSS feed -->
<rss version="2.0">
    <channel>
        <title>název feedu</title>
        <description>Výpis inzerátů s Sbazaru</description>
        <link>http://127.0.0.1:7777/</link>
            <title>název inzerátu</title>
            <link>http://odkaz na detail inzerátu</link>
            <guid isPermaLink="true">http://www.test.cz/</guid>
            <description><![CDATA[<img class="center" src="http://www.....cz/images/
        .jpg" alt=ta."/>jedna dvířka odšroubovaná, ale určitě se dají opravit a přidělat
        rozměry, cca 160 cm, š 120cm, h 37cm Simona, Brno, Jihomoravský ]]></description>
            <category> / Nábytek</category>
            <pubDate>Mon, 30 Jul 2017 09:41:33 +0000</pubDate>
        </item>
    </channel>
</rss>
<?php
}
