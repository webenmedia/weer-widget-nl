=== Weer Widget NL ===
Contributors: webenmedia
Tags: weer, nederlands, weersverwachting, weersvoorspelling, weer widget
Requires at least: 6.2
Tested up to: 6.7.1
Stable tag: 1.1
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gratis Nederlandse weer widget voor het huidige weer en de weersverwachting.

== Description ==
Met de **Weer Widget NL** kun je eenvoudig en gratis weersinformatie toevoegen aan je WordPress-website. Toon het actuele weer en de weersverwachtingen voor steden in Nederland en daarbuiten.

**Belangrijkste kenmerken:**
- Widgets zijn gratis.
- Responsieve en lichte code.
- Geen technische kennis vereist.
- Kies je taal: Nederlands, Engels en nog veel meer.

Neem voor maatwerkverzoeken contact op via [info@webenmedia.nl](mailto:info@webenmedia.nl).

== Installatie ==
1. Upload de plugin naar je WordPress-installatie via het menu Plugins > Nieuwe Plugin.
2. Activeer de plugin.
3. Voeg de widget toe op je website via de shortcode.

== Frequently Asked Questions ==
= Is de widget gratis? =
Ja, je kunt de weer widget gratis gebruiken op basis van fair use policy.

= Kan ik het weer voor iedere plaats toevoegen? =
Ja, je kunt iedere plaats ter wereld toevoegen om het weer voor te tonen op je website.

= Kan ik de taal aanpassen? =
Ja, je kunt de taal van de widget aanpassen naar wens.

== Screenshots ==
1. **Weer widget met 6-daagse weersverwachting.**
2. **Weer widget met 4-daagse weersverwachting.**
3. **Weer widget zonder weersverwachting.**

== Changelog ==
= 1.1 =
* Opgelost: Gebruik van onveilige `esc_sql` verwijderd en vervangen door veilige alternatieven.
* Toegevoegd: Unieke prefix (`weatherwidgetnl_`) voor klassen, functies en shortcodes om conflicten te voorkomen.
* Aangepast: Gebruik van globale variabele `$GLOBALS['weatherwidgetnl_languages']` voor taalselectie.
* Verbeterd: Alle SQL-query's aangepast om `wpdb::prepare` te gebruiken voor een betere beveiliging tegen SQL-injecties.
* Verbeterd: Dynamische tabelnamen correct geïmplementeerd zonder onveilige SQL-injecties.
* Verbeterd: Verbeterde validatie en sanering van gebruikersinvoer bij database-aanroepen.
* Verbeterd: Gebruik van unieke tabelnamen en cache-keys.
* Verbeterd: Veiligere en dynamische SQL-query-aanroepen met `wpdb->prepare`.

= 1.0 =
* Eerste release van de plugin.

== Upgrade Notice ==
= 1.1 =
* Beveiliging verbeterd door gebruik van `wpdb::prepare` voor veilige SQL-query's.
* Compatibiliteit met andere plugins verhoogd door unieke namen en veilige query's.

= 1.0 =
* Eerste release van de plugin.

== Beoordelingen ==
Ben je tevreden? Laat dan een beoordeling achter op de pluginpagina!