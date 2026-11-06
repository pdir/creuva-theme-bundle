# Creuva Theme Bundle

**Creuva – create something new.** Flexibles Theme für Contao 5.7 und Contao 6,
aufgebaut auf dem [Theme Components Bundle](https://github.com/contao-themes-net/theme-components-bundle).

## Voraussetzungen

- Contao 5.7 (PHP ≥ 8.3) oder Contao 6 (PHP ≥ 8.4)
- `contao-themes-net/theme-components-bundle` ^2.0 (wird automatisch installiert)
- `pdir/css-style-selector-bundle` ^2.1 (wird automatisch installiert)

## Installation

```bash
composer require pdir/creuva-theme-bundle
vendor/bin/contao-console contao:migrate
vendor/bin/contao-console creuva:install
```

Oder über den Contao Manager nach „creuva“ suchen und danach `creuva:install` ausführen.

## Einrichtung mit `creuva:install`

Der Befehl legt alles an, was das Theme braucht:

- Theme „Creuva“ mit den Modulen Hauptnavigation, Header, Footer – Marke,
  Footer-Navigationen, Copyright und untere Links
- Seitenlayout vom Typ „Modern“ mit dem Template `page/layout/creuva`, alle Module
  sind ihren Slots zugewiesen
- Zuweisung des Layouts zum Startpunkt

```bash
# Bestehende Website: Startpunkt wird abgefragt oder per ID übergeben
vendor/bin/contao-console creuva:install --root=1

# Neue Website mit Startseite, Kontakt, Impressum, Datenschutz, Zugänglichkeitserklärung, Sitemap und Suche
vendor/bin/contao-console creuva:install --with-pages --site-title="Meine Firma" --dns=example.org

# Komplette Demo-Website mit Beispielinhalten, Bildern, News und Formular
vendor/bin/contao-console creuva:install --demo
```

| Option | Beschreibung |
|--------|--------------|
| `--root=<ID>` | Startpunkt, der das Creuva-Layout erhält (mehrfach möglich) |
| `--with-pages` | Neuen Startpunkt mit Grundseiten anlegen (automatisch, wenn noch keiner existiert) |
| `--demo[=standard]` | Neuen Startpunkt mit der Demo anlegen (siehe unten) |
| `--site-title`, `--language`, `--dns` | Titel, Sprache (Standard `de`) und Domain des neuen Startpunkts |
| `--scheme` | Farbschema: `green` (Standard), `blue`, `red`, `yellow`, `petrol`, `violet`, `terracotta`, `ocean`, `indigo`, `gold`, `bordeaux`, `magenta`, `plum`, `orange`, `olive`, `sage`, `lime` |
| `--variant` | Farbmodus: `light` (Standard), `dark`, `auto` (nach Systemeinstellung) |
| `--typography` | Typografie: `modern` (Standard, Plus Jakarta Sans) oder `editorial` (Überschriften in Fraunces), mit jedem Farbmodus kombinierbar |
| `--theme-name` | Name des Themes (Standard „Creuva“), z. B. für eine zweite Variante |
| `--replace` | Website mit gleicher Domain und Sprache samt Theme vorher löschen – erneuter Import in einer Multisite (siehe [Inhalte zurücksetzen](#inhalte-zurücksetzen)) |
| `--reset` | Alle Contao-Inhalte vorher löschen (siehe `creuva:reset`) |
| `--force`, `--no-backup` | Mit `--reset`/`--replace`: ohne Rückfrage bzw. ohne Datenbank-Backup |

Kontakt-, Impressum-, Datenschutz-, Barrierefreiheits-, Sitemap- und Suchseiten einer bestehenden Website werden
anhand ihres Alias erkannt (`kontakt`, `impressum`, `datenschutz`, `barrierefreiheit`, `sitemap`, `suche`, …) und im
Header-Button, in der Header-Suche bzw. im Footer verlinkt. Modulnamen und Texte richten sich
nach der Sprache des Startpunkts (Deutsch oder Englisch).

## Suche

Der Creuva Header zeigt optional eine Lupe (Feld „Suche im Header“, Legende *Suche*). Sie öffnet
ein Suchfeld über der Seite, auch mit <kbd>Strg</kbd>+<kbd>K</kbd> (<kbd>⌘</kbd><kbd>K</kbd>)
oder <kbd>/</kbd>, und schickt die Suchbegriffe an die gewählte **Suchergebnisseite**. Auf dieser
Seite liegt das Core-Modul *Suchmaschine*; Creuva bringt dafür eigene Templates mit
(`mod_search.html.twig`, `search_default.html.twig`): großes Suchfeld, Treffer als Karten mit
Seitenpfad, hervorgehobenen Suchbegriffen und optionalem Bild sowie Tipps, wenn nichts gefunden
wurde. Ohne Häkchen erscheint keine Lupe.

`creuva:install --with-pages` und die Demo legen die Seite „Suche“ (versteckt, `noindex`, nicht im
Suchindex) mit dem Modul an und aktivieren die Suche im Header. In einer Demo-Definition
genügt `header: { search: suche }` (Alias der Ergebnisseite).

Das Seitenlayout nimmt nur den Bereich `main` in den Suchindex auf; Header, Footer und Copyright
erscheinen deshalb nicht in den Suchergebnissen. Nach einem Update den Suchindex einmal neu
aufbauen (Backend › System › Wartung oder `contao:crawl`).

## Suchmaschinen und Linkvorschau

Das Creuva-Layout gibt auf allen indexierbaren Seiten Open-Graph-Tags aus: Titel, Beschreibung, Adresse und
das erste Hero- oder Bild-Element im Hauptbereich (1200 × 630 px, keine SVG-Dateien). Startpunkte von
`creuva:install` haben das Canonical-Tag aktiviert, bei bestehenden Websites lässt es sich im Startpunkt
einschalten. Der Styleguide steht auf `noindex,nofollow`.

## Demo-Inhalte

Es gibt vier Demos, jede wird als eigener Startpunkt importiert (bei einer bestehenden Website
mit `--dns` oder `--language` auf eine andere Domain bzw. Sprache legen):

| Demo | Befehl | Für |
|------|--------|-----|
| Standard / Creative | `creuva:install --demo` | Agenturen, Freelancer, kleine Unternehmen |
| Unternehmen | `creuva:install --demo=unternehmen` | Mittelstand, Industrie, Handwerk |
| Portfolio / Studio | `creuva:install --demo=portfolio` | Fotografen, Architekten, Kreativstudios |
| Sportverein | `creuva:install --demo=sportverein` | Fußball- und Sportvereine, Verbände |

`creuva:install --demo` importiert die Demo „Standard / Creative“:

- **Seiten:** Startseite, Leistungen (Webdesign, Entwicklung, Beratung), Projekte mit
  sechs Projektseiten, Über uns mit Team, Preise, News & Wissen mit FAQ und Downloads,
  Kontakt mit Danke-Seite, Impressum, Datenschutz, Zugänglichkeitserklärung, Sitemap, Suche und eine 404-Seite („Diese Seite hat
  sich verlaufen.“ mit Illustration und Links zu Leistungen, Projekten und Wissen); dazu der
  [Styleguide](#styleguide) (`styleguide: true` in der Demo-Definition)
- **Module:** Hauptnavigation, Header mit CTA und Suche, Footer mit vier Spalten und Social-Media-Links,
  News-Liste und -Leser, Sitemap, Suchmaschine
- **Bildgrößen** im Theme (Logo, Hero, Karte, Bild & Text, Breit, Team, Avatar, News, Galerie),
  auf die alle Bilder der Demo verweisen – mit mehreren Breiten, WebP und verzögertem Laden
  (siehe *Bildgrößen der Demos* unter [Eigene Demo aus der Dateiverwaltung](#eigene-demo-aus-der-dateiverwaltung))
- **News-Archiv** mit drei ausführlichen Beiträgen (nur wenn das News-Bundle installiert ist)
- **Autor** der Artikel und News: der erste Administrator (sonst der erste Backend-Benutzer).
  Ältere Importe ohne Autor ergänzt `contao:migrate`.
- **Kontaktformular** mit Weiterleitung auf die Danke-Seite (ohne E-Mail-Versand,
  bitte im Formulargenerator ergänzen)
- **Dateien** unter `files/creuva-demo/standard/` (Illustrationen, Icons, PDFs) inklusive
  Alt-Texten; der Ordner ist öffentlich
- **Favicon** im Startpunkt (Contao-Feld „Favicon“, ausgeliefert unter `/favicon.ico`);
  die Vorlage liegt als SVG unter `assets/favicon/favicon.svg`. Liegt neben dem Favicon eine
  SVG-Datei gleichen Namens (z. B. `design/favicon.svg`), bindet das Seitenlayout sie
  zusätzlich als `<link rel="icon" type="image/svg+xml">` ein; `/favicon.ico` bleibt Fallback

Die Demo enthält außerdem den **Style-Switcher** (Zahnrad am linken Rand), mit dem
Besucher Farbschema, Farbmodus, Typografie und Eckenradius live ausprobieren können.

### Styleguide

Der Styleguide zeigt alle Elemente, Templatevarianten, CSS-Klassen und Module des Themes
auf einer Seite: Farben, Typografie, Buttons, Listen und Tabellen, Medien, alle
Templatevarianten, Kennzahlen/Timeline/Fakten, Akkordeon, Theme-Components-Elemente,
Hintergründe, Animationen, Formular (alle Feldtypen), Breadcrumb und News-Liste.

Er ist unabhängig von der Demo und lässt sich jeder Website mit dem Creuva-Layout
hinzufügen:

```bash
vendor/bin/contao-console creuva:styleguide --root=1
vendor/bin/contao-console creuva:styleguide --root=1 --replace   # nach einem Theme-Update neu anlegen
```

- Seite „Styleguide“ (Alias `styleguide`) am Ende der Website, nicht in der Navigation,
  nicht im Suchindex und mit `noindex,nofollow`
- Eigene Module, ein Formular und ein News-Archiv mit dem Präfix „Styleguide – “,
  die Bildgrößen des Themes werden wiederverwendet
- Dateien unter `files/creuva-demo/styleguide/`
- Definition: `styleguide/styleguide.yaml` (gleiche Syntax wie die Demos). Neue Elemente
  und Klassen bitte dort ergänzen.

Eine Demo installiert den Styleguide mit `styleguide: true` gleich mit. Im Footer lässt er
sich über den Alias `styleguide` verlinken.

Die Demo verwendet ausschließlich Core-Elemente, Creuva-Elemente und Templatevarianten
und läuft deshalb unter Contao 5.7 und 6 identisch. Die Definition liegt in
`demo/standard/demo.yaml` und lässt sich als Vorlage für weitere Demos nutzen
(neuer Ordner `demo/<name>/` → `--demo=<name>`).

### Demo „Portfolio / Studio“

`creuva:install --demo=portfolio` importiert die Demo für Fotografen, Architekten,
Designer und Kreativstudios („Studio Lichtung“, Typografie Editorial, Farbschema Terracotta,
kleine Ecken):

- **Seiten:** Startseite mit Vollbild-Hero, Arbeiten mit sechs Projektseiten (Architektur,
  Ausstellung, Produkt, Hotel, Bürohaus, freie Serie), Bildarchiv (Masonry-Galerie mit
  Lightbox), Studio mit Team und Auszeichnungen, Leistungen mit Preisen und FAQ, Journal,
  Kontakt mit Danke-Seite, Impressum, Datenschutz, Zugänglichkeitserklärung, Sitemap, Suche und 404-Seite
- **Projektseiten:** Vollbild-Hero, Projektdaten (Fakten), Text, je eine andere
  Galerie-Anordnung oder ein Slider mit Thumbnails und ein Link zum nächsten Projekt
- **Module:** Journal-Liste und -Leser, Breadcrumb, Sitemap, Suche; die Kundenlogos sind ein
  Inhaltselement „Logoleiste“ und lassen sich direkt im Artikel bearbeiten
- **Bilder:** generierte Illustrationen (stilisierte Fotos) unter `demo/portfolio/files/`,
  die sich durch echte Fotos gleicher Proportion ersetzen lassen

Alle Elemente sind demounabhängig und auch in der Standard-Demo und im Styleguide nutzbar,
u. a. die Bildform **„Vollbild (Text auf dem Bild)“** des Heros, die Templatevariante
**`creuva_tile`** („Bildkachel“: Bild in der Proportion seiner Bildgröße, ganze Kachel
klickbar), das Inhaltselement **„Logoleiste“** (`creuva_logos`, ohne eigenen Hintergrund, den
bestimmt der Abschnitt) und die Raster-Klasse **„Versetzt“** (`u-grid-offset`, zweite Spalte tiefer).
Im Dark Mode stehen die Logos der Logoleiste auf einer hellen Fläche, damit dunkle und farbige Logos
lesbar bleiben. Ausnahmen sind der Hintergrund „Primärfarbe“ und die Klasse `u-logos-original`, beide
für weiße Logos.

Seiten-Aliase in Insert-Tags wie `{{link_url::kontakt}}` ersetzt der Import durch die IDs
der importierten Seiten. So verlinkt eine zweite Demo in derselben Installation (z. B. mit
`--dns`) nicht versehentlich auf gleichnamige Seiten der ersten.

### Demo „Unternehmen“

`creuva:install --demo=unternehmen` importiert eine klassische Firmenwebsite für Mittelstand,
Industrie, Dienstleister und Handwerksbetriebe („Kessler Metalltechnik GmbH“, Variante Light,
Farbschema Ozean, eckig) – seriöser und strukturierter als die Standard-Demo:

- **Seiten:** Startseite mit Vollbild-Hero, Kennzahlen, Leistungen, Branchen, Projektablauf,
  Kundenlogos, Stellen und Aktuellem; Leistungen (Konstruktion, Fertigung, Montage & Service)
  mit Maschinenpark; Branchenlösungen (Maschinenbau, Bau & Architektur, Energie &
  Anlagenbau, Lebensmittelindustrie); Referenzen; Unternehmen mit Geschichte, Zertifikaten,
  Geschäftsführung und Standorten; Karriere mit Stellenangeboten, Bewerbungsablauf und
  Online-Bewerbung (Datei-Upload); Download-Center; Aktuelles; Kontakt mit Ansprechpartnern
- **Stellenangebote** sind Nachrichten im Archiv „Stellenangebote“; die Subline wird zu
  Schlagworten wie „Vollzeit · Neuburg · ab sofort“.
- **Bilder, Logos, Zertifikate und PDFs:** generierte Platzhalter unter
  `demo/unternehmen/files/`

Neu im Theme und demounabhängig:

- Listenvorlage **`news_creuva_job`** für Nachrichtenlisten: Stellenanzeige als Karte mit
  Titel, Schlagworten (aus der Subline, getrennt durch „·“) und Teaser
- Templatevariante **„Prozess-Schritte“** (`description_list/creuva_steps`): nummerierte
  Schritte, ab Tablet-Breite nebeneinander mit Verbindungslinie
- **Downloads** zeigen Dateityp und Größe („PDF · 245 KB“)

### Demo „Sportverein“

`creuva:install --demo=sportverein` importiert die Demo für Fußballvereine, andere
Sportvereine und kleinere Verbände („TSV Rotenbach 1921“, Variante Light, Farbschema Rot,
mittlere Ecken, Vereinswappen als Logo):

- **Seiten:** Startseite mit Vollbild-Hero, nächstem Spiel und letztem Ergebnis, News,
  Mannschaften, Tabelle, Kennzahlen und Partnern; Mannschaften mit Seiten für 1. Herren,
  Damen und Jugend (Trainingszeiten, Trainerteam, Tabelle); Spielplan mit Tabelle; Verein
  mit Geschichte, Vorstand und Sportpark; News; Sponsoren mit Sponsoring-Paketen; Mitglied
  werden mit Beiträgen, Online-Antrag und Beitrittserklärung (PDF); Kontakt, Suche und 404
- **Spielplan und Ergebnisse** sind Termine im Kalender „Spielplan“ (Event-Liste mit den
  nächsten Terminen und den letzten Ergebnissen). Ihre Daten sind relativ zum Tag des
  Imports, die nächsten Spiele liegen also immer in der Zukunft.
- **Bilder, Wappen und Sponsorenlogos:** generierte Illustrationen unter
  `demo/sportverein/files/`, die sich durch echte Fotos und Logos ersetzen lassen

Neu im Theme und demounabhängig:

- Inhaltselement **„Spielpaarung“** (`creuva_match`): Wettbewerb, Heim- und Gastmannschaft
  mit Wappen, Anstoß, Spielort, optional Ergebnis und Button. Ohne Ergebnis zeigt es die
  Anstoßzeit (nächstes Spiel), mit Ergebnis den Endstand.
- Tabellen-Klasse **„Liga-Tabelle“** (`u-table-league`): kompakte Tabelle mit zentrierten
  Zahlen; die eigene Mannschaft wird hervorgehoben, wenn sie in der Tabelle fett ist.
- Datumsfelder lassen sich in einer Demo-Definition relativ angeben, z. B.
  `creuvaMatchDate: 'date:next saturday 15:00'`.

### Eigene Demo aus der Dateiverwaltung

Eine Demo kann auch in einem Ordner unter `files/` liegen, z. B. für Kundenprojekte, ohne
das Bundle zu ändern. Der Ordner hat denselben Aufbau wie `demo/standard/`:

```
files/meine-demo/
├── demo.yaml       Seiten, Inhalte, Module, Formulare, News (Schema wie demo/standard/demo.yaml)
└── files/          Bilder, Icons, Downloads (optional, referenziert mit file:<pfad>)
```

```bash
vendor/bin/contao-console creuva:install --demo=files/meine-demo
```

Bildgrößen (Theme › Bildgrößen, siehe
[Contao-Doku](https://docs.contao.org/5.x/dev/framework/image-processing/image-sizes/)) werden
unter `imageSizes:` mit den Feldern von `tl_image_size` definiert, Media Queries
(`tl_image_size_item`) unter `items`. Der Import legt sie im Creuva-Theme an; Inhalte und Module
verweisen mit `image_size:<key>` darauf, eigene Größen bleiben als `[breite, höhe, modus]` möglich:

```yaml
imageSizes:
  logo: { name: 'Logo', width: 480, height: 120, resizeMode: 'box', densities: '2x', skipIfDimensionsMatch: true }
  card:
    name: Karte (4:3)
    width: 800
    height: 600
    resizeMode: crop
    densities: 400w, 600w, 800w
    sizes: '(min-width: 80rem) 384px, (min-width: 44rem) 31vw, (min-width: 30rem) 46vw, calc(100vw - 2rem)'
    lazyLoading: true
    formats: [webp]   # Standard; [] = ohne moderne Formate
    items:
      - { media: '(max-width: 40rem)', width: 640, height: 640, resizeMode: 'crop' }
pages:
  - title: Start
    articles:
      - elements:
          - { type: 'image', singleSRC: 'file:bilder/foto.jpg', size: 'image_size:card' }
modules:
  newslist: { name: 'News', type: 'newslist', news_archives: ['news_archive:blog'], imgSize: 'image_size:card' }
```

**Bildgrößen der Demos:** Jede Größe ist für einen Platz im Layout gedacht, damit der Browser
nicht mehr lädt als nötig (PageSpeed: „Bilder richtig dimensionieren“). Inhaltsbilder haben
mehrere Breiten (`…w`) und eine `sizes`-Angabe passend zu Containerbreite (76rem), Spalten und
Breakpoints; kleine Bilder fester Größe (Team, Avatar) haben `2x`. Alle Größen außer Hero,
Vollbild und News-Detail (meist das größte Bild oben, siehe *Bevorzugtes Laden*) laden verzögert.
JPEG- und PNG-Bilder bekommen zusätzlich WebP, wenn die Bildbibliothek des Servers es kann
(`formats`, Standard `[webp]`; AVIF bleibt aus, weil GD dafür sehr langsam ist).

| Key | Name | Für |
|-----|------|-----|
| `hero` | Hero | Bild im Hero-Element |
| `cover` | Vollbild (16:9) | Hero über die volle Breite, auf Smartphones hochkant |
| `card` | Karte (4:3) | Karten in drei oder vier Spalten, Slider mit mehreren Karten |
| `media` | Bild & Text (4:3) | Templatevariante „Bild & Text“, Bilder und Karten in zwei Spalten |
| `wide` | Breit | Bild über die Inhaltsbreite (auf Smartphones 4:3) |
| `tile-portrait`, `tile-landscape` | Kachel hoch/quer | Bildkacheln (Portfolio, Sportverein) |
| `slider` | Slider (3:2) | Bilder im Slider über die Inhaltsbreite (Portfolio) |
| `team`, `avatar` | Team, Avatar | Personenbild (max. 14rem) und Kundenstimme (3rem) |
| `news`, `news-detail` | News-Teaser, News-Detail | Newsliste und Kopf des Newslesers |
| `gallery` | Galerie (responsiv) | Galerien, `sizes` setzt das Galerie-Template pro Bild |

Header und Footer-Marke verwenden die Bildgröße mit dem Key `logo`. Ohne eine solche Größe legt
der Installer eine eigene Bildgröße „Logo“ an (480 × 120 px Box, 2x-Dichte), auch ohne Demo.
Das Logo selbst wird unter `header:` bzw. `brand:` mit `logo:` gesetzt, eine abweichende Größe
mit `logoSize`:

```yaml
design:
  scheme: green       # Farbschema, Farbmodus, Typografie und Eckenradius des Seitenlayouts
  variant: light      # (der Startpunkt bleibt leer, damit Änderungen im Layout greifen)
  typography: modern
  radius: large
  logoHeight: 4rem    # Logohöhe des Layouts (Standard 2.25rem), der Header wächst mit
  stylesheets: ['files/design/custom.css']   # eigenes CSS, wird angelegt, falls nicht vorhanden
header:
  logo: file:design/logo.png
brand:
  logo: file:design/logo-hell.png
  logoSize: image_size:logo-footer   # oder ['', '128', 'proportional']
```

Demo-Module lassen sich zusätzlichen Slots des Layouts zuordnen, z. B. eine Logoleiste
(Modul `creuva_logos`, Logos verlinkt über die Metadaten der Dateien) über dem Footer
(im Inhalt einer Seite gibt es dafür das gleichnamige Inhaltselement „Logoleiste“):

```yaml
files:
  partner/stadt.png: { alt: 'Stadt Musterstadt', link: 'https://example.org/' }
modules:
  partner: { name: 'Partner', type: 'creuva_logos', headline: 'Unsere Partner', multiSRC: ['file:partner/stadt.png'], creuvaLogosVariant: 'primary', creuvaLogosHeight: '6rem' }
layout:
  prefooter: [partner]
```

Die Links der unteren Leiste (neben dem Copyright) legt `bottom:` fest, z. B. die Rechtstexte,
damit der Footer selbst mit Marke und drei Linkspalten in eine Zeile passt (ohne `bottom:` steht
dort nur die Sitemap). Ein Kontaktbereich über die volle Breite unter den Spalten (Dachzeile, Titel, Text, Fakten mit
Häkchen und Button; ab 72rem in einer Zeile) ist ein HTML-Modul mit der Klasse `c-footer-contact`
im Slot `footer`:

```yaml
bottom: ['impressum', 'datenschutz', 'agb', 'sitemap']
modules:
  footerContact:
    name: 'Footer – Kontakt'
    type: html
    html: '<div class="c-footer-contact"><div class="c-footer-contact__text"><p class="c-footer-contact__kicker">Beratung</p><p class="c-footer-contact__title">Fragen zum Projekt?</p><p>{{email::info@example.org}}</p></div><ul class="c-footer-contact__facts"><li>Fakt 1</li><li>Fakt 2</li></ul><a class="c-button c-button--primary" href="{{link_url::kontakt}}">Kontakt</a></div>'
layout:
  footer: [footerContact]
```

Termine (Kalender-Bundle) werden wie News unter `events:` definiert; das Datum ist ein
`strtotime()`-Ausdruck (`"2026-12-05"`, `"5 December"` = aktuelles Jahr), Uhrzeiten als `HH:MM`.
Module referenzieren den Kalender mit `calendar:<key>`:

```yaml
events:
  programm:
    title: Programm
    jumpTo: page:termin
    items:
      - { title: 'Eröffnung', alias: 'eroeffnung', date: '23 November', startTime: '17:00', endTime: '18:00', location: 'Marktplatz' }
modules:
  eventlist: { name: 'Termine', type: 'eventlist', cal_calendar: ['calendar:programm'], cal_format: 'next_all' }
```

- Der Pfad ist relativ zum Projektverzeichnis und muss innerhalb von `files/` liegen
  (nicht in `files/creuva-demo`).
- Die Dateien werden nach `files/creuva-demo/local-<ordnername>/` kopiert; der Quellordner
  bleibt unverändert und kann mit `--replace` (Multisite) oder `--reset` beliebig oft neu importiert
  werden.
- Mit `filesFolder: inhalte` in der `demo.yaml` landen die Dateien stattdessen in
  `files/inhalte/` (der Ordner wird öffentlich geschaltet). Er wird von `creuva:reset` nicht
  gelöscht; bei einem erneuten Import werden die Dateien überschrieben und behalten ihre UUID.
- Den Quellordner nicht öffentlich schalten, sonst ist die `demo.yaml` im Web abrufbar.

### Website als Demo exportieren

`creuva:export` speichert eine bestehende Website als Demo-Vorlage unter `var/exports/<name>/`.
Exportiert werden der Seitenbaum mit Artikeln und Inhaltselementen, die darin verwendeten Module,
Formulare, News und Termine, die Bildgrößen sowie alle referenzierten Dateien inklusive Alt-Texten.
Die Styleguide-Seite wird nicht exportiert. Stattdessen steht `styleguide: true` in der
Definition, und der Import legt den Styleguide aus `styleguide/styleguide.yaml` neu an:

```bash
# Startpunkt wird abgefragt (bei mehreren Websites) oder per ID übergeben
vendor/bin/contao-console creuva:export
vendor/bin/contao-console creuva:export 1 --name=kunde-x --title="Kunde X"
```

```
var/exports/kunde-x/
├── demo.yaml       IDs sind durch page:<alias>, module:<key>, image_size:<key>, file:<pfad> … ersetzt
└── files/          die verwendeten Dateien (Ordner in Galerien werden aufgelöst)
```

Die Vorlage lässt sich in jede Installation importieren: Ordner nach `files/kunde-x` kopieren
und `creuva:install --demo=files/kunde-x` ausführen (oder als Bundle-Demo nach `demo/<name>/`).

**Im Backend** (Layout › Creuva › *Website exportieren*, nur für Administratoren) führt dasselbe
Formular `creuva:export` aus. Darunter stehen alle Exporte aus `var/exports` mit Titel, Datum,
Anzahl der Dateien und Größe; sie lassen sich als ZIP herunterladen und löschen. Das ZIP wird
beim Download erzeugt (`Exporter/ZipWriter`, mit `ZipArchive` oder ohne die PHP-Erweiterung
„zip“) und danach wieder gelöscht. Eigene Tabellen anderer Erweiterungen sind nicht Teil des
Exports; für den Umzug einer kompletten Website ein Datenbank-Backup verwenden.

- Header-Button, Logos, Marke (Claim, Social-Media-Links) und Footer-Spalten werden aus den
  Creuva-Modulen des Seitenlayouts übernommen, Logo-Höhe, Farbschema, Variante, Typografie und
  Eckenradius aus Seitenlayout und Startpunkt (als `design:`, der Import setzt sie im Seitenlayout).
  Eigene Farben des Layouts werden nicht exportiert.
- Vom Startpunkt werden Datums-/Zeitformate und das Favicon übernommen
  (`root: { favicon: file:… }`).
- Bildgrößen: alle Größen des Themes, dem das Seitenlayout gehört, und alle in Inhalten und
  Modulen verwendeten Größen, jeweils mit ihren Media Queries. Größen aus der Konfiguration
  (`contao.image.sizes`, `_name`) bleiben Verweise und müssen im Ziel ebenfalls definiert sein.
- Seiten-Links in Insert-Tags (`{{link_url::12}}`) werden auf Aliase umgestellt. Insert-Tags mit
  anderen IDs (`{{insert_module::3}}`, `{{file::…}}`) und die Elemente „Inhaltselement“ und
  „Artikel“ (Verweise) lassen sich nicht übertragen; der Command listet sie als Warnung auf.
- Empfänger-Adressen von Formularen werden nicht exportiert.
- Mit `--force` wird ein vorhandener Export gleichen Namens ersetzt.

### Inhalte zurücksetzen

Der Demo-Import überschreibt keine bestehenden Inhalte. Um die Demo erneut zu importieren
oder eine Installation in den Zustand nach der Neuinstallation zu versetzen, gibt es
`creuva:reset`:

```bash
# Alle Inhalte löschen (mit Rückfrage und Datenbank-Backup)
vendor/bin/contao-console creuva:reset

# Inhalte löschen und die Demo neu importieren
vendor/bin/contao-console creuva:install --demo --reset
```

- Geleert werden alle Contao-Tabellen (`tl_*`): Seitenstruktur, Artikel, Inhaltselemente,
  Themes, Module, Layouts, Formulare, News, Versionen, Suchindex usw. Die IDs beginnen
  wieder bei 1.
- Erhalten bleiben Backend-Benutzer und -Gruppen, Mitglieder und Mitgliedergruppen, die
  Dateiverwaltung (`tl_files`) sowie technische Tabellen (Sessions, Cronjobs, Message Queue).
- Von den Dateien wird nur der Demo-Ordner `files/creuva-demo` gelöscht.
- Vorher wird ein Backup unter `var/backups/creuva-reset__<Zeitstempel>.sql.gz` angelegt;
  zurückspielen mit `contao:backup:restore <Dateiname>`. Ohne Backup: `--no-backup`.
- Ohne Rückfrage (z. B. in Skripten): `--force`.

#### Eine Website einer Multisite ersetzen (`--replace`)

`--reset` löscht **alle** Websites. Laufen mehrere Websites in einer Installation, ersetzt
`--replace` nur die Website, die der Import neu anlegen würde:

```bash
vendor/bin/contao-console creuva:install --demo=files/meine-demo \
    --site-title="Meine Firma" --dns=example.org --theme-name="Meine Firma" --replace
```

Gelöscht werden:

- die Startpunkte mit derselben Domain (`--dns`) und Sprache (`--language`) samt allen
  Unterseiten, Artikeln und Inhaltselementen (auch verschachtelte)
- das Theme mit dem Namen aus `--theme-name` und die Themes, deren Layouts diese Website nutzt,
  jeweils mit Layouts, Modulen und Bildgrößen
- Formulare (Inhaltselemente und Module vom Typ *Formular*), News-Archive und Kalender (aus den
  Modulen der gelöschten Themes) samt Feldern, Nachrichten, Events und deren Inhalten – aber nur,
  wenn keine andere Website sie verwendet

Sicherheitsregeln:

- Wird das Theme aus `--theme-name` auch von einer anderen Website genutzt (Layout oder Modul),
  bricht der Befehl ab, ohne etwas zu löschen. Andere gemeinsam genutzte Themes, Formulare,
  Archive und Kalender bleiben erhalten (mit Hinweis).
- Vor dem Löschen zeigt der Befehl alle betroffenen Datensätze und fragt nach; ohne Rückfrage:
  `--force`. Vorher wird ein Backup unter `var/backups/creuva-replace__<Zeitstempel>.sql.gz`
  angelegt (`--no-backup` zum Abschalten).
- Dateien bleiben erhalten; der Import überschreibt die Demo-Dateien, ihre UUIDs bleiben gleich.
- Bild- und Sitemap-Cache werden geleert, der Seiten-Cache nur, wenn ein Reverse Proxy
  konfiguriert ist (sonst bei Bedarf `cache:clear`). Den Suchindex danach mit `contao:crawl`
  neu aufbauen.
- Gibt es noch keine passende Website, legt `--replace` sie einfach neu an.
- Nicht kombinierbar mit `--reset` und `--root`; erfordert `--demo` oder `--with-pages`.

### Zugänglichkeitserklärung

Jede Demo bringt die Seite `zugaenglichkeitserklaerung` mit, die im Footer verlinkt ist. Jeder
Abschnitt ist ein eigenes Text-Element. Abschnitte, die nicht auf jede Website passen (PDF-Dokumente,
Videos und Audio, Karten, Drittanbieter, BFSG, Schlichtung, Screenreader,
Maßnahmen), sind ausgeblendet und lassen sich im Artikel über das Augen-Symbol einblenden.
`--with-pages` legt die Seite mit einem Platzhalter an.

Der Text ist ein Muster, das Theme allein macht eine Website nicht konform. Der Stand und
die Angaben müssen zur jeweiligen Website passen.

## Manuelle Einrichtung

1. **Layout → Themes → Neues Theme** anlegen.
2. **Module** anlegen:
   - *Navigation* (Navigationsmenü, 2 Ebenen) für das Hauptmenü
   - *Creuva › Creuva Header* – Logo, Navigationsmodul, CTA-Button, optional Suche; enthält das mobile Menü
   - optional *Suchmaschine* auf einer versteckten Seite „Suche“ (siehe [Suche](#suche))
   - *Creuva › Creuva Marke* – Logo, Claim und Social-Media-Links für den Footer
   - *Individuelle Navigation* mit Überschrift für jede Footer-Spalte (z. B. Leistungen, Unternehmen, Rechtliches)
   - optional *Eigener HTML-Code* für das Copyright und eine individuelle Navigation für die Links unten rechts
   - optional *Creuva › Creuva Style-Switcher* – Vorschau der Farbschemen im Frontend (z. B. für eine Demo-Website)
3. **Seitenlayout** anlegen: Layouttyp **„Modern“**, Template **`page/layout/creuva`**, Module den Slots zuweisen:

   | Slot     | Inhalt                                                     |
   |----------|------------------------------------------------------------|
   | `top`    | optionale Leiste (z. B. Kontrast-/Schriftgrößenumschalter), Style-Switcher |
   | `header` | Creuva Header                                              |
   | `main`   | Artikel                                                    |
   | `footer` | Creuva Marke + Footer-Navigationen                         |
   | `bottom` | Copyright, Sitemap, Cookie-Einstellungen                   |

   Die untere Leiste enthält immer den Link „powered by contao-themes.net“; er ist Teil der
   Lizenz und darf nur mit einer Lizenz ohne Copyright-Link entfernt werden (siehe `LICENSE`).
   Footer-Spalten und untere Leiste liegen gemeinsam in einem `<footer>`.

   Barrierefreiheit: Jedes Navigationsmodul sollte unter *Experten-Einstellungen* eine
   eindeutige ARIA-Beschriftung bekommen (z. B. „Hauptnavigation“, „Leistungen“,
   „Service-Links“), die Überschriften der Footer-Spalten sind `h2`. In Formularen bei Name,
   E-Mail, Telefon usw. das Feld *Autovervollständigen* setzen (`name`, `email`, `tel`,
   `organization`, `bday`). Der Installer und die Demos machen das bereits.

4. Unter **Creuva-Design** Farbschema, Farbmodus (Light, Dark, System), Typografie (Modern, Editorial), Eckenradius,
   Animationen sowie optional eigene Primär- und Akzentfarbe und eine Logohöhe (z. B. `4rem`,
   Standard `2.25rem`) festlegen.

Hintergründe, Raster, Abstände usw. setzen Redakteure im Bereich *CSS-Stil* der Elemente,
Artikel und Module (siehe [CSS-Klassen im CSS Style Selector](#css-klassen-im-css-style-selector)).

## Inhaltselemente & Module

| Typ | Beschreibung |
|-----|--------------|
| Creuva › Hero | Einstiegsbereich mit Dachzeile, Überschrift + hervorgehobenem Teil, Text, zwei Buttons, Bild in mehreren Formen (*Organisch* mit Maske, *Abgerundet* als ganzes Bild mit Schatten und Akzentform dahinter, *Bild unverändert*, *Bild unverändert mit Akzentform* ohne Schatten und Beschnitt, ideal für freigestellte Bilder mit transparentem Hintergrund, und *Vollbild*) und Badge als kleine Karte mit Häkchen, eine Zeile pro Punkt (Bild und Text optional) |
| Creuva › Creuva Header | Logo, Hauptnavigation (mit Dropdowns), CTA-Button, mobiles Menü |
| Creuva › Creuva Marke | Logo, Claim, Social-Media-Links (Plattform wird anhand der URL erkannt) |

Gestaltet werden außerdem die Contao-Standardelemente (Text, Liste, Beschreibungsliste,
Tabelle, Bild, Galerie, Content-Slider, Akkordeon, Downloads, Hyperlink, Code, Toplink, Formulare,
Breadcrumb, Suche), die News-Module (Liste als Karten, Leser, Archivmenü, Seitenzahlen), die Module
der Managed Edition (Event-Liste mit Datums-Badge, Event-Leser, Monats- und Minikalender,
Event-Menü, FAQ-Liste, -Seite und -Leser, Newsletter-Anmeldung, -Liste und -Leser, Kommentare,
Listing) und die Elemente des
Theme Components Bundles 2.0 (Contentbox, Teaserbox, Preisbox, Feature-Element, Simplebox,
Icon-Tile, Modal).

### Templatevarianten

Unter *Template-Einstellungen › Individuelles Template* stehen diese Varianten zur Wahl:

| Element | Variante | Verwendung |
|---------|----------|------------|
| Text | `creuva_card` | Karte mit Bild, Kategorie (Subline), Titel und Text; der letzte Link macht die ganze Karte klickbar |
| Text | `creuva_feature` | Icon-Box mit rundem Icon (SVG oder kleines Bild); beim Hover hebt sich die Karte und das Icon wird mit der Primärfarbe gefüllt. Im CSS Style Selector unter *Elementoptionen › Icon-Box*: „Ohne Karte“ (`u-feature-plain`, z. B. Vorteile in einer Reihe) oder „Icon links“ (`u-feature-inline`, z. B. Kontaktdaten) |
| Text | `creuva_cta` | Call-to-Action-Box: dunkle Fläche, Dachzeile, zweifarbige Überschrift und Text links, die Links im letzten Absatz als Buttons rechts (der erste gefüllt, weitere als Umriss). Optionen im CSS Style Selector: Farbe unter *Farben des Elements* (Weiß, Grau, Akzent hell, Akzentfarbe, Rahmen; Text und Buttons passen sich an), *Call to Action › Zentriert, Buttons unter dem Text* (`u-cta-stacked`), volle Fensterbreite mit *Breite › Volle Breite* (`u-full-bleed`, für eine Fläche ohne Abstand im Artikel „Innenabstand oben und unten: Kein“) |
| Text | `creuva_media` | Bild & Text: Bild links oder rechts neben dem Text, oder oben bzw. unten (*Bildausrichtung*, dann am besten eine breite Bildgröße), Dachzeile, zweifarbige Überschrift, Text mit Häkchenliste; Links im letzten Absatz werden zu Buttons (der erste als Primär-Button) |
| Text | `creuva_testimonial` | Kundenstimme: Zitat im Text, Name als Überschrift, Funktion als Subline, Porträt als Bild |
| Text | `creuva_team` | Teammitglied: Porträt, Name, Funktion (Subline), Kontakt |
| Text | `creuva_price` | Preis-Karte: Paket als Überschrift, Preis als Subline, Leistungsliste, letzter Link als Button; mit `u-featured` hervorgehoben |
| Beschreibungsliste | `creuva_stats` | Kennzahlen als Karten: Begriff = Zahl, Beschreibung = Bezeichnung; Zeichen vor/nach der Zahl („+“, „%“, „Uhr“) werden hervorgehoben, die Zahlen zählen beim Scrollen hoch. Bereiche wie „11–20 Uhr“ bekommen die Einheit ebenfalls, zählen aber nicht hoch; Datumsangaben („24.11.“, „24.11.2026“) bleiben unverändert. Optional ein Icon über jeder Zahl: Feld *Icons (Kennzahlen)*, in der Reihenfolge der Einträge; der Kreis lässt sich mit den Icon-Klassen (`u-icon-*`) einfärben, SVG-Icons erscheinen im Farbschema |
| Beschreibungsliste | `creuva_timeline` | Zeitleiste: Begriff = Jahr/Schritt, Beschreibung = Ereignis; horizontal, sobald das Element breit genug ist, in einer Rasterspalte vertikal |
| Beschreibungsliste | `creuva_facts` | Fakten als Karte, z. B. Projektdaten: Begriff = Bezeichnung („Kunde“), Beschreibung = Wert |
| Galerie | `creuva_slider` | Galerie als Slider (Swiper wie der Content-Slider): *Bilder pro Reihe* legt fest, wie viele Bilder ein Slide zeigt (1–6; auf Tablets höchstens zwei, auf Smartphones eines); Pfeile und Punkte blättern um so viele Bilder weiter. Das Format kommt aus der Bildgröße (für gleich hohe Slides eine Größe mit festem Zuschnitt wählen); *Großansicht* öffnet die Lightbox |

In Kästen mit `u-bg-primary` oder `u-bg-accent` wird ein Link, der allein im letzten
Absatz steht, automatisch als Button dargestellt (Call-to-Action-Box). In Texten mit
`u-lead` und in Abschnittsköpfen (Elementgruppe mit `u-grid-2 u-align-end`) wird er zum
Pfeil-Link („Alle Leistungen ansehen →“).

### Dachzeile

Text-Elemente und Beschreibungslisten haben das Feld **Dachzeile** (kleiner Text in
Großbuchstaben über der Überschrift). Zusammen mit einer Subline mit „Inline“ ergibt das
die Kombination aus Dachzeile und zweifarbiger Überschrift wie auf der Startseite der Demo
(„KREATIVE PARTNER AUF AUGENHÖHE – Mehr als nur eine **Agentur.**“).

### Abschnittskopf

Überschrift links, Einleitung und Link rechts: eine Elementgruppe mit den Klassen
`u-grid-2 u-align-end`, darin ein Überschrift-Element und ein Text-Element (Klasse `u-lead`)
mit dem Link allein im letzten Absatz.

### Content-Slider

Im Element **Content-Slider** ist jedes enthaltene Element ein Slide: Bilder, Karten, Kundenstimmen,
Texte oder ganze Elementgruppen. Creuva ergänzt im Bereich *Slider-Einstellungen*:

- **Elemente pro Ansicht** (1–4): auf Tablets höchstens zwei, auf Smartphones eines
- **Navigationspfeile** und **Punkte**: einzeln abschaltbar, sie sitzen in einer Leiste unter den
  Slides und verdecken keine Inhalte; passen alle Elemente in die Ansicht, werden sie ausgeblendet
- **Thumbnails**: Vorschaubilder unter dem Slider aus dem ersten Bild jedes Slides (Elemente ohne
  Bild bekommen ihre Nummer), z. B. für eine Bildergalerie

Autoplay (*Verzögerung*), Geschwindigkeit, Startslide und Endlosschleife sind die Contao-Felder.
Karten und Boxen in einer Reihe sind gleich hoch. Beispiele zeigt der [Styleguide](#styleguide).

### Akkordeon

Jeder Eintrag ist eine Karte mit einem runden Plus/Minus-Schalter am Ende. Ein Icon vor der
Frage wird mit einer Klasse am Akkordeon-Element ergänzt: `u-accordion-icon` (Fragezeichen),
`u-accordion-info` oder `u-accordion-chat`. Für ein eigenes Icon zusätzlich zu
`u-accordion-icon` eine eigene Klasse vergeben und das Icon per CSS setzen:

```css
.content-accordion.mein-icon {
  --accordion-icon-mask: url("/files/icons/paket.svg");
}
```

### Abschnitte mit Hintergrund

Die Hintergrundklassen (`u-bg-muted`, `u-bg-accent`, `u-bg-primary`) können auch im
**Artikel** unter *Experten-Einstellungen › CSS-Klasse* eingetragen werden – der Artikel
wird dann zum Abschnitt mit Hintergrund über die volle Breite.

**Abstände zwischen Abschnitten:** Jeder Artikel hat oben und unten einen Abschnittsabstand.
Folgen zwei Artikel ohne Hintergrund oder mit demselben Hintergrund aufeinander, entfällt der
obere Abstand des zweiten. Sonst würde sich der Abstand verdoppeln, obwohl beide wie eine Fläche
wirken. Einzelne Artikel lassen sich im CSS Style Selector unter *Abstände* anpassen: Innen- und
Außenabstand jeweils oben und unten, nur oben oder nur unten.

### Überschriften mit Dachzeile oder Akzent

Das Theme Components Bundle erweitert Überschriften um eine *Subline*. Creuva nennt das Feld
**„Zusatz zur Überschrift“**:

- Zusatz **ohne** Häkchen → kleine Zeile über der Überschrift („WAS WIR TUN“)
- Zusatz mit **„Zweifarbige Überschrift“** → Zusatz und Überschrift in einer Zeile, die
  eigentliche Überschrift erscheint in der Akzentfarbe („Mehr als nur eine **Agentur.**“)
- Zusatz mit **„Zusatz darunter anzeigen“** → Unterzeile

Das Creuva-Feld **„Dachzeile“** (Text, Beschreibungsliste) steht zusätzlich über der
Überschrift. So lassen sich Dachzeile und zweifarbige Überschrift kombinieren.

### News-Beiträge

Der News-Leser zeigt einen Kopfbereich wie der Hero: Archiv, Datum und Lesezeit als Dachzeile,
Überschrift, Unterüberschrift als Einleitung und Autor, daneben das Bild des Beitrags (Bildgröße
im Modul *News – Leser*). Die Inhaltselemente folgen in der Lesespalte.

**Bildunterschrift des News-Bildes:** In Listen erscheint keine. Im News-Leser erscheint sie nur, wenn sie
im Beitrag selbst unter *Metadaten überschreiben* eingetragen ist; die Bildunterschrift aus der
Dateiverwaltung wird nicht übernommen (`src/EventListener/NewsImageCaptionListener.php`).

Im Beitrag gibt es das Feld **„Hervorgehobener Teil“** (neben der Überschrift): Der eingetragene
Text erscheint im Beitrag in der Akzentfarbe, z. B. „**Barrierefreiheit wird Pflicht** – was
Websites jetzt brauchen“. Er muss genau so in der Überschrift vorkommen. Bleibt das Feld leer,
ist die Überschrift einfarbig. In Listen, im Seitentitel und in Suchmaschinen bleibt die
Überschrift unverändert.

### Text formatieren (Akzentfarbe, Buttons …)

Der Texteditor hat das Menü **„Formate“** mit den Textstilen des Themes:

- *Text:* Akzentfarbe (grün), Markierung, Gedämpft – für markierte Wörter
- *Absatz:* Einleitung (größer), Dachzeile
- *Links:* Button, Button (sekundär) – den Link markieren und das Format wählen

Technisch: Das Text-Element nutzt die Editor-Konfiguration `tinyCreuva`
(`contao/templates/be_tinyCreuva.html.twig`, erweitert `@Contao/be_tinyMCE.html.twig`; als
Twig-Template funktioniert sie in Contao 5.7 und 6).

### CSS-Klassen im CSS Style Selector

Alle Creuva-Klassen stehen im [CSS Style Selector](https://github.com/pdir/css-style-selector-bundle)
als Stile bereit, und zwar im Bereich *CSS-Stil* von Inhaltselementen, Artikeln und
Modulen. Die Bereiche der Tabelle unten sind Kategorien (z. B. *Abstände*), darin liegen
die Gruppen (z. B. *Innenabstand oben und unten*). Jede Gruppe erscheint im Element als
Auswahlfeld und erlaubt genau einen Stil. Jedes Element zeigt nur die Gruppen an, die für
seinen Typ passen (z. B. *Raster* nur bei Elementgruppen, *Buttons* nur bei Hyperlink- und
Text-Elementen).

Kategorien und Gruppen liegen als normale Datensätze in der Datenbank, die Stile als Zeilen
(CSS-Klasse und Titel) in ihrer Gruppe. Unter *Layout › CSS Stile* lassen sie sich ändern,
sortieren (Gruppen und Stile per Drag & Drop) und um eigene Kategorien und Gruppen
ergänzen; Bereiche und Elementtypen gelten jeweils für die ganze Gruppe
(*Veröffentlichung*). Die Vorlage ist `config/css-styles.yaml`.
Importiert wird sie

- automatisch von `creuva:install`,
- bei bestehenden Websites einmalig von `contao:migrate`, solange noch keine Creuva-Gruppe
  existiert,
- nach einem Theme-Update mit neuen Klassen mit diesem Befehl:

```bash
vendor/bin/contao-console creuva:css-styles
```

Der Import ergänzt nur fehlende Kategorien, Gruppen und Stile. Änderungen im Backend
(Titel, Elementtypen, entfernte Stile, eigene Kategorien und Gruppen) bleiben erhalten. Die
importierten Kategorien und Gruppen erkennt der Import an ihrer Kennung (`creuvaGrid`,
`creuvaGrid.columns` …), die Stile einer Gruppe an ihrer CSS-Klasse.

Gruppen aus der Version vor den Kategorien (Titel „Bereich › Gruppe“) verschiebt
`contao:migrate` in die Kategorie ihres Bereichs und kürzt den Titel auf den Gruppennamen,
sofern er nicht im Backend geändert wurde.

- Der Demo-Import wählt die Klassen der Demo-Elemente im CSS Style Selector vor.
- Bei bestehenden Websites übernimmt die Migration (`contao:migrate`) Creuva-Klassen
  aus dem Feld *CSS-Klasse* in die Stil-Auswahl. Das gilt nur für Datensätze ohne
  ausgewählten Stil, die Klassen selbst bleiben unverändert.
- **Umstieg vom Style Manager:** Die Klassen stehen weiterhin im Feld *CSS-Klasse*, das
  Frontend bleibt also unverändert. Nach dem Update legt `contao:migrate` die Creuva-Gruppen
  an und überträgt die Auswahl. Eigene Style-Manager-Gruppen müssen als CSS-Stile neu
  angelegt werden. Danach können die Tabellen `tl_style_manager_archive` und
  `tl_style_manager` sowie die Spalten `styleManager` gelöscht werden.

**Farben des Elements:** Karten, Icon-Boxen, Kundenstimmen, Preis-Karten und die Boxen des
Theme Components Bundles bekommen über den CSS Style Selector eine Fläche (Weiß, Grau, Akzent hell,
Haupt- oder Akzentfarbe) oder nur einen farbigen Rahmen. Auf dunklen Flächen wechseln Text,
Links und Dachzeilen automatisch in die Kontrastfarbe. Der Kreis hinter dem Icon einer
Icon-Box ist separat einstellbar. Bei Haupt- und Akzentfarbe wird das Icon weiß, das
funktioniert mit einfarbigen SVG-Icons. Andere Icon-Farben sind bei Bild-Icons nicht möglich.
Technisch setzen die Klassen nur CSS-Variablen (`--card-bg`, `--card-border`, `--icon-bg` …,
siehe `assets/scss/_element-styles.scss`), die alle Karten lesen.

**Buttons:** Ein *Hyperlink*-Element wird mit „Primär-Button“, „Sekundär-Button“,
„Akzent-Button“ oder „Outline“ (Haupt- bzw. Akzentfarbe, nur Rahmen) zum Button. Bei einem *Text*-Element mit dieser Auswahl werden die Links im
**letzten Absatz** zu Buttons, nebeneinander. In der Templatevariante „Bild & Text“
und in Call-to-Action-Boxen (`u-bg-primary`, `u-bg-accent`) passiert das automatisch.

### Übersicht der Klassen

Alle Klassen, die als CSS-Stile bereitstehen:

| Bereich | Klassen |
|---------|---------|
| Hintergrund | `u-bg-surface`, `u-bg-muted`, `u-bg-accent`, `u-bg-primary` |
| Breite | `u-full-bleed` (Hintergrund über die volle Breite), `u-narrow` |
| Rahmen | `u-card`, `u-featured` (hervorgehobene Preis-Karte) |
| Raster (Elementgruppe) | `u-grid-2`, `u-grid-3`, `u-grid-4`, Ausrichtung `u-align-start` (Spalten nicht strecken), `u-align-center`, `u-align-end`, Spaltenbreite bei zwei Spalten `u-cols-wide-start`, `u-cols-wide-end` (3 : 2) |
| Tabelle | `u-table-compare` (Paket-Vergleich: Spalten 2–4 farbig, „✓“/„–“ als Icons) |
| News | `u-news-no-author` am Modul Newsliste, Newsleser oder Newsarchiv: blendet den Autor aus, das Datum bleibt (Contao 5 hat die Einstellung „Meta-Felder“ nicht mehr). Dafür setzen die Creuva-Templates `news_latest`/`news_short` den Autor in ein eigenes Element |
| Abstände | Innenabstand `u-py-*` (oben und unten), `u-pt-*` (nur oben), `u-pb-*` (nur unten); Außenabstand `u-my-*`, `u-mt-*`, `u-mb-*` – jeweils `0`, `s`, `m`, `l`, `xl` |
| Text | `u-text-left/center/right`, `u-lead`, `u-muted`, `u-accent`, `u-kicker` |
| Buttons | `u-button` (Primär), `u-button-secondary` (weiß), `u-button-accent` (Akzentfarbe), `u-button-outline`, `u-button-outline-accent` (nur Rahmen; Schrift und Rahmen in der Akzent-Textfarbe, damit auch helle Akzente lesbar bleiben) – für das Hyperlink-Element und die Links im letzten Absatz eines Text-Elements |
| Farben des Elements | Fläche oder Rahmen von Karten, Icon-Boxen, Kundenstimmen, Preis-Karten und Theme-Components-Boxen: `u-card-white`, `u-card-muted`, `u-card-soft`, `u-card-primary`, `u-card-accent`, `u-card-outline-primary`, `u-card-outline-accent`, `u-card-flat`, `u-card-none` (ohne Hintergrund); bei den Kennzahlen gilt die Auswahl für jede einzelne Zahl (ohne Auswahl nur auf dunklem Hintergrund eine leichte Fläche, mit `u-card-none` auch dort keine). Icon der Icon-Box und der Kennzahlen: `u-icon-soft` (hell, Standard), `u-icon-accent` (mittel, Icon weiß), `u-icon-primary` (dunkel, Icon weiß), `u-icon-white`, `u-icon-light` (weißer Kreis, Icon schwarz – auch auf dunklem Hintergrund), `u-icon-plain`. SVG-Icons werden im Farbschema eingefärbt (CSS-Maske, Icon in Akzentfarbe bzw. Kontrastfarbe); `u-icon-original` (Gruppe *Icon-Farbe*) zeigt sie in den Farben der Datei, z. B. mehrfarbige Icons. Andere Bilder (PNG, JPG) behalten ihre Farben |
| Icon-Box | `u-feature-plain` (ohne Karte), `u-feature-inline` (Icon links) |
| Call to Action | `u-cta-stacked` (zentriert, Buttons unter dem Text) |
| Galerie | Bildgröße „Galerie (responsiv)“ wählen (mehrere Breiten, der Browser lädt passend zur Kachel), „Großansicht“ öffnet die Creuva-Lightbox; Spalten über „Bilder pro Reihe“ (1–6); Anordnungen `u-gallery-mosaic` (großes Bild links, daneben ein breites und zwei kleine, je vier Bilder), `u-gallery-mosaic-right`, `u-gallery-featured` (erstes Bild über die volle Breite), `u-gallery-masonry` (Bilder in eigener Höhe – Bildgröße ohne Zuschnitt wählen) |
| Animation | `u-reveal-up`, `u-reveal-left`, `u-reveal-right`, `u-reveal-down`, `u-reveal-zoom`, `u-reveal-blur`, `u-reveal-fade`, `u-reveal-none` (keine Animation), `u-reveal-stagger` (Inhalte nacheinander), `u-no-countup` (Kennzahlen nicht hochzählen) |
| Elemente | `u-accordion-icon`, `u-accordion-info`, `u-accordion-chat` (Akkordeon-Icon), `u-media-plain` (Bild & Text ohne Akzentfläche) |
| Sichtbarkeit | `u-hide-mobile`, `u-hide-desktop` |

Die Klassen funktionieren auch direkt im Feld *CSS-Klasse*, z. B. in Bereichen ohne
CSS-Stil-Auswahl.

Ein Klassen-Set für das Advanced Classes Bundle bringt Creuva nicht mehr mit. Das Bundle
bleibt installiert, weil das Theme Components Bundle es voraussetzt. `contao:migrate`
entfernt das frühere Set „Creuva“ aus Startpunkten und Formularen.

## Bilder in Galerien und Lightbox

**Bildgröße:** Eine Galerie hat nur eine Bildgröße für alle Bilder. Damit trotzdem jede
Kachel passend geladen wird, bringen Demo und Styleguide die Bildgröße
**„Galerie (responsiv)“** mit: proportional, Breiten 320/480/640/800/1200/1600 px als `srcset`.
Das Galerie-Template (`contao/templates/content_element/gallery.html.twig`) ersetzt das
`sizes` der Bildgröße pro Bild: passend zu Anordnung (`u-gallery-*`), Position und
„Bilder pro Reihe“, begrenzt auf die Inhaltsbreite (1216 px). Beim Mosaik bekommt das große
Bild z. B. `(min-width: 80rem) 608px, (min-width: 48rem) 50vw, 100vw`, die kleinen 304px
bzw. 25vw. Ist ein Bild breiter als seine Kachel, wird es seitlich beschnitten und größer als die
Kachel dargestellt; `sizes` wird dann entsprechend erhöht, damit es nicht unscharf wird. So lädt
der Browser für kleine Kacheln kleine und für große Bilder große Varianten
(`src/Image/GalleryImages.php`). Zugeschnitten wird per CSS (`object-fit`), die Bilder bleiben
unbeschnitten gespeichert. Der **„Wichtige Bereich“** aus der Dateiverwaltung wird dabei als
`object-position` gesetzt, damit das Motiv in der Kachel sichtbar bleibt. Für eigene Galerien
diese Bildgröße wählen oder nach demselben Prinzip anlegen (Breiten genügen, `sizes` setzt das
Template).

**Bildformat:** Das Format eines Bildes legt die Bildgröße fest, nicht das CSS. Das gilt für Galerie,
Galerie-Slider, Bild & Text, Hero, News, Suchergebnisse und die Teaser-Box. Galerie-Raster und
Galerie-Slider brauchen für gleich hohe Bilder eine Größe mit festem Zuschnitt (z. B. „Karte (4:3)“),
Mosaik und „Erstes Bild groß“ füllen ihre Kacheln mit jeder Größe, Masonry braucht eine Größe ohne
Zuschnitt. Fest bleiben nur Formen, die das Element selbst vorgibt: das runde Team-Bild (1:1), die
Vorschaubilder des Sliders und die Wappen im Spiel-Element.

**Lightbox:** Mit *Großansicht/Neues Fenster* öffnen Bilder der Galerie und des Bild-Elements
die Creuva-Lightbox (`assets/js/creuva.js`, `components/_lightbox.scss`). Sie bietet einen
dunklen Hintergrund, Bildunterschrift bzw. Titel aus den Metadaten (höchstens zwei Zeilen;
der Alt-Text wird nicht angezeigt), Zähler, Vor/Zurück, Tastatur (Esc,
Pfeiltasten) und Wischen auf dem Smartphone. Bilder mit demselben `data-lightbox`-Wert,
also einer Galerie, bilden eine Gruppe. Die Lightbox-Skripte von Contao (jQuery/MooTools)
werden nicht benötigt.

**Bevorzugtes Laden:** Das erste große Bild einer Seite (mindestens 400 px breit, im ersten
Artikel des Hauptbereichs, z. B. das Hero-Bild) bekommt automatisch `fetchpriority="high"`
und kein Lazy Loading, damit es als Erstes geladen wird (`src/EventListener/ImagePriorityListener.php`).
Beginnt eine Seite mit Text, bleiben alle Bilder unverändert, ebenso wenn ein Template bereits
selbst `fetchpriority` setzt.

## Animationen und Hover-Effekte

Im Seitenlayout legt **Creuva-Design › Animationen** fest, was beim Scrollen eingeblendet wird:

| Einstellung | Wirkung |
|-------------|---------|
| Alle Abschnitte (Standard) | Alle Elemente im Hauptbereich blenden von unten ein, Raster (`u-grid-*`) und News-Listen Element für Element, „Bild & Text“ von beiden Seiten |
| Nur per CSS-Klasse | Nur Elemente mit einer Klasse aus „Animation“ (CSS-Stile, siehe Übersicht der Klassen) |
| Keine | Keine Scroll-Animationen, keine Einstiegsanimation im Hero, Zahlen werden sofort angezeigt |

- Die Klassen `u-reveal-*` legen die Richtung je Element fest und haben Vorrang vor der
  automatischen Animation; `u-reveal-none` nimmt ein Element (oder einen ganzen Artikel) aus.
- Inhalte, die beim Laden schon sichtbar sind, werden nie ausgeblendet; ohne JavaScript ist
  alles sofort sichtbar.
- Der Hero blendet beim Laden kurz ein (0,35 s, längere Animationen verschlechtern den Speed Index
  in PageSpeed); die Fläche hinter dem Bild und das Badge bewegen sich erst nach 3 Sekunden.
- Besucher mit der Systemeinstellung „Bewegung reduzieren“ sehen keine Animationen.
- Hover-Effekte (Karten heben sich, Bilder zoomen, Pfeile wandern mit) sind immer aktiv.
- Dauer, Weg und Verzögerung lassen sich per CSS anpassen:

```css
:root {
  --reveal-duration: 0.6s;   /* Standard 0.8s */
  --reveal-distance: 1.5rem; /* Standard 2.5rem */
  --reveal-stagger: 60ms;    /* Verzögerung je Element im Raster */
  --hover-lift: -4px;        /* Anheben von Karten beim Hover */
}
```

## Templates anpassen

Alle Templates sind Twig-Templates und lassen sich wie gewohnt in `templates/`
überschreiben, z. B. `templates/content_element/creuva_hero.html.twig`:

```twig
{% extends '@Contao/content_element/creuva_hero.html.twig' %}
```

## Farbschemen

Die Farbschemen sind in `config/color-schemes.json` gepflegt – die Datei ist die einzige
Quelle für das CSS, die Auswahl im Seitenlayout, den Style-Switcher und `creuva:install --scheme`:

```json
"petrol": {
  "label": { "de": "Petrol", "en": "Petrol" },
  "primary": "#0f3a40",
  "accent": "#0b7d7d"
}
```

| Schlüssel | Pflicht | Verwendung |
|-----------|---------|------------|
| `primary` | ja | Buttons, dunkle Flächen, Links; tönt außerdem Hintergründe, Rahmen und Text |
| `accent` | ja | Akzente, Badges, Icons, Fokusrahmen |
| `accentText` | nein | Akzent als Textfarbe (Standard: `accent`), z. B. dunkleres Gelb |
| `accentContrast` | nein | Textfarbe auf dem Akzent (Standard: Weiß) |
| `darkAccent` | nein | Primär- und Akzentfarbe der Variante „Dark“ (Standard: `accent` aufgehellt) |

Neues Schema: Eintrag ergänzen und `npm run build` ausführen. Der Build prüft die
WCAG-Kontraste (Text 4,5:1, große Schrift und Grafiken 3:1) für Light und Dark und bricht mit
einer Meldung ab, wenn ein Schema sie nicht erreicht (`assets/build/color-schemes.js`). Geprüft
werden Text, gedämpfter Text und Akzenttext auf allen Flächen (Hintergrund, Weiß, Grau, Akzent
hell), Schrift auf Primär- und Akzentfarbe sowie auf Primär-Karten im Dark Mode. Reicht der
Akzent als Textfarbe nicht, ein dunkleres `accentText` angeben. Das Schema `default` gilt ohne Auswahl.

Eigene Primär- und Akzentfarben im Seitenlayout prüft die Live-Vorschau: Sie zeigt einen
Hinweis, wenn weiße Schrift auf der Primärfarbe oder die Akzentfarbe als Schrift auf Weiß
unter 4,5:1 liegt.

Im Seitenlayout werden die Schemen als Farbpunkte zur Auswahl angeboten; darunter zeigt eine
Live-Vorschau Überschrift, Buttons und Boxen im gewählten Schema – inklusive Variante,
Eckenradius und eigener Farben (`public/backend.css` und `backend.js`, nur im Seitenlayout geladen).

Das Seitenlayout gibt das Schema als `data-scheme` am `<html>`-Element aus; eigene
Primär-/Akzentfarben aus dem Layout überschreiben es.

Im Startpunkt der Website (Root-Seite) lassen sich Farbschema, Farbmodus, Typografie und Eckenradius
unter „Creuva-Design“ zusätzlich festlegen. Diese Werte haben Vorrang vor dem Seitenlayout;
ein Schema im Startpunkt ersetzt auch die eigenen Farben des Layouts. So können mehrere
Websites ein Layout nutzen und trotzdem unterschiedlich aussehen. Der Installer und die Demos
lassen die Felder im Startpunkt leer („Wie im Seitenlayout“), damit Änderungen im Seitenlayout
sichtbar werden; ältere Demo-Definitionen mit `creuvaScheme` … unter `root:` landen ebenfalls im
Seitenlayout. Die frühere Variante „Corporate“
ist jetzt das Schema „Blau“, die frühere Variante „Editorial“ die Typografie „Editorial“ im Farbmodus Light
(bestehende Layouts und Startpunkte werden per Migration umgestellt). Farbmodus und Typografie sind
unabhängig voneinander, z. B. Editorial mit Dark.

### Style-Switcher

Das Frontend-Modul *Creuva Style-Switcher* blendet am linken Rand ein Zahnrad ein, über das
Besucher Farbschema, Farbmodus (Light, Dark, System), Typografie (Modern, Editorial) und Eckenradius wechseln. Die Auswahl wird nur im Browser
(localStorage) gespeichert und gilt auf allen Seiten mit dem Modul. Per URL lässt sich eine
Kombination direkt verlinken, z. B. `?scheme=red&theme=dark&typography=editorial&radius=none`. Das Modul im Slot
`top` platzieren, damit die Auswahl vor dem ersten Rendern greift.

In der Frontend-Vorschau zeigt der Switcher Administratoren zusätzlich den Button
„Für die Website speichern“. Er speichert die Auswahl im Startpunkt der aktuellen Website,
leert den Seiten-Cache dieser Website und gilt danach für alle Besucher. Redakteure ohne
Admin-Rechte und normale Besucher sehen den Button nicht; der Endpunkt
(`/contao/creuva/style-switcher`) prüft Admin-Rechte und Request-Token.

Mit der Moduloption „Nur für angemeldete Backend-Benutzer“ ist der Switcher für Besucher
unsichtbar: Er erscheint nur, wenn man im Backend angemeldet ist (im Frontend und in der
Vorschau). Besucher können das Design dann nicht ändern, eine früher im Browser gespeicherte
Auswahl gilt für sie nicht mehr. Für Live-Websites diese Option setzen, ohne sie sieht jeder
den Switcher (Demo-Website).

### Farbmodus „System“

Der Farbmodus „Light oder Dark nach Systemeinstellung“ (`auto`, im Seitenlayout oder im
Startpunkt) zeigt die Website hell oder dunkel, je nachdem, was im Betriebssystem bzw. Browser
des Besuchers eingestellt ist, und wechselt mit, wenn sich die Einstellung ändert. Ein kleines
Skript im `<head>` setzt dafür `data-theme` vor dem ersten Rendern (kein Aufblitzen); ohne
JavaScript bleibt die Seite hell. Im Style-Switcher heißt die Option „System“.

## Schriften und Druck

- **Schriften:** Plus Jakarta Sans (alle Gewichte von 200 bis 800) und Fraunces (Überschriften der
  Typografie „Editorial“) werden als variable WOFF2-Dateien mit dem Zeichensatz Latin ausgeliefert und
  selbst gehostet, also ohne Anfragen an Google. Plus Jakarta Sans (27 KB) wird bewusst nicht per
  Preload geladen, weil das mit `creuva.css` konkurriert und das erste Rendern verzögert; bis die Datei da
  ist, steht der Text in der Ersatzschrift (`font-display: swap`). Fraunces lädt der Browser nur, wenn
  die Typografie sie verwendet. Lizenz: SIL Open Font License 1.1
  (siehe `THIRD-PARTY-NOTICES.md`).
- **Druck:** Gedruckt wird nur der Inhalt in Schwarz auf Weiß, ohne Header, Footer, Breadcrumb,
  Slider-Steuerung und Hintergründe. Links im Text erscheinen mit ihrer Adresse, Akkordeons sind
  aufgeklappt, Karten und Bilder werden nicht über Seiten getrennt.

## Eigenes CSS

Sämtliche Creuva-Styles liegen in CSS-Cascade-Layern (`creuva.*`). Eigene Styles
ohne Layer haben dadurch immer Vorrang – ohne `!important` oder hohe Spezifität.
Farben, Abstände und Typografie sind als CSS Custom Properties definiert
(`assets/scss/base/_tokens.scss`) und können einfach überschrieben werden:

```css
:root {
  --scheme-primary: #123456; /* Primärfarbe inkl. getönter Hintergründe */
  --radius-l: 0.75rem;
}
```

Eigene CSS-Dateien aus der Dateiverwaltung (z. B. `files/design/custom.css`) werden im
Seitenlayout unter *Creuva-Design › Eigene Stylesheets* ausgewählt und nach `creuva.css`
geladen (mit Änderungszeitpunkt als Cache-Busting). Der Ordner muss öffentlich sein.
In einer Demo legt `design: stylesheets:` die Datei an, falls sie noch nicht existiert;
eine vorhandene Datei wird nie überschrieben.

## Entwicklung

Die gebauten Assets in `public/` werden mit ausgeliefert, damit bei der Installation
kein Node.js nötig ist. Nach Änderungen in `assets/`:

```bash
npm install
npm run build     # Produktion → public/creuva.*, public/backend.*, public/manifest.json
npm run dev       # Watch-Modus mit Sourcemaps
```

`public/manifest.json` sorgt über das Asset-Package `creuva_theme` für Cache-Busting
(`{{ asset('creuva.css', 'creuva_theme') }}`).

Die Demobilder und -PDFs werden aus `assets/demo/*.html` erzeugt (Headless Chrome,
z. B. `chrome --headless --screenshot --window-size=1600,1200 "file://…/images.html#projekt-gruenwerk"`).

### Lokal in einer Contao-Installation testen

```bash
composer config repositories.creuva '{"type":"path","url":"../creuva-theme-bundle","options":{"symlink":true}}'
composer require pdir/creuva-theme-bundle:@dev
```

## Struktur

```
assets/            Quellcode (SCSS, JS) – wird nicht mit ausgeliefert
config/            Symfony-Services, Farbschemen (color-schemes.json), CSS-Stile (css-styles.yaml)
contao/dca/        DCA-Erweiterungen (tl_layout, tl_content, tl_module, tl_news, tl_page, tl_css_style_group)
contao/languages/  Übersetzungen (de, en)
contao/templates/  Twig-Templates (Seitenlayout, Elemente, Templatevarianten, Module, Komponenten)
demo/              Demo-Definitionen (demo.yaml) und Demo-Dateien
public/            gebaute Assets → /bundles/creuvatheme/
src/               PHP (Bundle, Manager-Plugin, Controller, DCA-Listener, Installer + Befehle creuva:install, creuva:reset, creuva:styleguide, creuva:css-styles)
styleguide/        Styleguide-Definition (styleguide.yaml) und Dateien
theme-idee/        Konzept und Entwürfe
```

## Bekannte Einschränkungen

- Die Barrierefreiheits-Werkzeuge (Kontrast, Schriftgröße) und die Animationen beim
  Scrollen des Theme Components Bundles nutzen die Hooks `generatePage` bzw.
  `parseFrontendTemplate`, die Contao bei Layouts vom Typ „Modern“ nicht ausführt.
  Die Einstellungen im Startpunkt haben deshalb im Creuva-Layout keine Wirkung;
  Creuva bringt eigene Animationen mit (siehe „Animationen und Hover-Effekte“).
- Die Demo-Dateien (SVG-Icons, PDFs) werden direkt aus `files/creuva-demo/` geladen und
  brauchen den Symlink `public/files/creuva-demo` (`contao:symlinks`). Unter Windows schlägt
  das ohne Administratorrechte bzw. Entwicklermodus fehl – dann fehlen Icons und Downloads.
  Für die lokale Entwicklung hilft eine Verzeichnis-Junction:
  `mklink /J public\files\creuva-demo files\creuva-demo`. `creuva:install --reset` und
  `contao:symlinks` kommen mit einer vorhandenen Junction aber nicht zurecht und brechen
  ab. Die Junction deshalb vorher mit `rmdir public\files\creuva-demo` entfernen (löscht
  nur den Link).

## Lizenz

Proprietär – © pdir GmbH, siehe [LICENSE](LICENSE).
Hinweise zu Bibliotheken und Schriften Dritter: [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
