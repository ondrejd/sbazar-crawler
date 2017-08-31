<?php
/**
 * Skript pro vytváření RSS z nabídky serveru Sbazar - nastavení crawleru.
 *
 * Tento soubor se automaticky ukládá po odeslání administračního formuláře,
 * takže není nutnost tento soubor editovat ručně.
 *
 * Vygenerováno: 31.8.2017 11:07
 *
 * @author Ondřej Doněk, <ondrejd@gmail.com>
 * @package sbazar_crawler
 */

return [
    'base_url'    => "https://www.sbazar.cz/",
    'category'    => "815-tablety-ctecky-knih",
    'price_from'  => 4500,
    'price_to'    => 25000,
    'town'        => null,
    'sort'        => null,
    'current_url' => "https://www.sbazar.cz/815-tablety-ctecky-knih/cela-cr/cena-od-4500-do-4500-kc",
    'channel'     => [
        'title'       => "Zkušební feed",
        'description' => "Popisek zkušebního feedu",
        'language'    => 'cs',
    ],
];