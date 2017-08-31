# Crawler pro [Sbazar][1]

Jednoduchý PHP skript, který združuje tyto funkce:

- jednoduché administrační rozhraní pro nastavení crawleru
- část, která je míněna k napojení na [CRON][2], která stáhne inzeráty a uloží je do [RSS][3] souboru
- generování výsledného [RSS][3] souboru pro uživatele

## Instalace

Skript vyžaduje přinejmenším <b>PHP 5.6.*</b>, ale běží i na <b>PHP 7.0</b> a použito je pouze standardní [DOM][4] rozšíření.

### Postup instalace

1. stáhneme [poslední vydání][5] ze serveru a rozbalíme jej na náš server
2. ujistíme se, že skript může zapisovat do vlastní složky `sbazar-crawler`
3. otevřeme v editoru soubor `index.php` a upravíme hodnoty těchto konstant `SC_ADMIN_PASS` a `SC_FEED_SELF_URL`

## Použití

Skript poskytuje tři URL adresy:

- `http://127.0.0.1/sbazar-crawler/?admin={PASS}` pro administrační rozhraní, kde `{PASS}` nahraďte platným heslem (viz. __Postup instalace__, bod __3__)
- `http://127.0.0.1/sbazar-crawler/?cron=1` pro spuštění stahování inzerátů - toto je pro pravidelní volání přes [CRON][2]
- `http://127.0.0.1/sbazar-crawler/` pro zobrazení vygenerovaného [RSS][3] kanálu

[1]: https://www.sbazar.cz/
[2]: https://cs.wikipedia.org/wiki/Cron
[3]: https://cs.wikipedia.org/wiki/RSS
[4]: http://php.net/manual/en/book.dom.php
[5]: https://github.com/ondrejd/sbazar-crawler/releases
