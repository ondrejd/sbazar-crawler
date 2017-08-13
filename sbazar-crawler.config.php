<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - nastavení crawleru.
 *
 * Tento soubor se automaticky ukládá po odeslání administračního formuláře,
 * takže není nutnost tento soubor editovat ručně.
 *
 * Vygenerováno: 12.8.2017 17:27
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 */

return [
    'base_url'    => "https://www.sbazar.cz/",
    'category'    => "8-dum-byt-zahrada",
    'price_from'  => null,
    'price_to'    => null,
    'town'        => null,
    'sort'        => null,
    'current_url' => "/8-dum-byt-zahrada",
    'channel'     => [
        'title'       => "Zkušební feed",
        'link'        => "http://localhost:7777/sbazar-crawler.php",
        'description' => "Popisek zkušebního feedu.",
        'language'    => 'cs',
    ],
];
