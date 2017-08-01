# Crawler pro [Sbazar][1]

Jednoduchý PHP skript, který združuje tyto funkce:

- jednoduché administrační rozhraní pro nastavení crawleru
- část, která je míněna k napojení na [CRON][2], která stáhne inzeráty a uloží je do [RSS][3] souboru
- generování výsledného [RSS][3] souboru pro uživatele

## Instalace

...

## Použití

Skript poskytuje tři URL adresy:

- `.../sbazar-crawler.php?admin={PASS}` pro administrační rozhraní, kde `{PASS}` nahraďte platným heslem
- `.../sbazar-crawler.php?cron=1` pro spuštění stahování inzerátů - toto je pro pravidelní volání přes [CRON][2]
- `.../sbazar-crawler.php?feed={ID}` pro zobrazení vybraného [RSS][3] kanálu (kde `{ID}`) indentifikuje zvolený kanál)

[1]: https://www.sbazar.cz/
[2]: https://cs.wikipedia.org/wiki/Cron
[3]: https://cs.wikipedia.org/wiki/RSS

