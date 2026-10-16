# Creuva Theme Bundle

**Creuva – create something new.** Flexibles Theme für Contao 5.7 und Contao 6,
aufgebaut auf dem [Theme Components Bundle](https://github.com/contao-themes-net/theme-components-bundle).

## Voraussetzungen

- Contao 5.7 (PHP ≥ 8.3) oder Contao 6 (PHP ≥ 8.4)
- `contao-themes-net/theme-components-bundle` ^2.0 (wird automatisch installiert)

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
- Zuweisung des Layouts und des Klassen-Sets „Creuva“ (Advanced Classes) zum Startpunkt

```bash
# Bestehende Website: Startpunkt wird abgefragt oder per ID übergeben
vendor/bin/contao-console creuva:install --root=1

# Neue Website mit Startseite, Kontakt, Impressum, Datenschutz und Sitemap
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
| `--variant` | Design-Variante: `light` (Standard), `dark`, `editorial` |
| `--theme-name` | Name des Themes (Standard „Creuva“), z. B. für eine zweite Variante |

Kontakt-, Impressum-, Datenschutz- und Sitemap-Seiten einer bestehenden Website werden
anhand ihres Alias erkannt (`kontakt`, `impressum`, `datenschutz`, `sitemap`, …) und im
Header-Button bzw. Footer verlinkt. Modulnamen und Texte richten sich nach der Sprache
des Startpunkts (Deutsch oder Englisch).

## Demo-Inhalte

`creuva:install --demo` importiert die Demo „Standard / Creative“ als eigenen Startpunkt
(bei einer bestehenden Website mit `--dns` oder `--language` auf eine andere Domain bzw.
Sprache legen):

- **Seiten:** Startseite, Leistungen (Webdesign, Entwicklung, Beratung), Projekte mit
  sechs Projektseiten, Über uns mit Team, Preise, News & Wissen mit FAQ, Downloads
  und „Elemente & Module“ (Styleguide aller Komponenten), Kontakt mit Danke-Seite,
  Impressum, Datenschutz und Sitemap
- **Module:** Hauptnavigation, Header mit CTA, Footer mit vier Spalten und Social-Media-Links,
  News-Liste und -Leser, Sitemap
- **News-Archiv** mit vier Beiträgen (nur wenn das News-Bundle installiert ist)
- **Kontaktformular** mit Weiterleitung auf die Danke-Seite (ohne E-Mail-Versand,
  bitte im Formulargenerator ergänzen)
- **Dateien** unter `files/creuva-demo/standard/` (Illustrationen, Icons, PDFs) inklusive
  Alt-Texten; der Ordner ist öffentlich

Die Demo enthält außerdem den **Style-Switcher** (Zahnrad am linken Rand), mit dem
Besucher Farbschema, Variante und Eckenradius live ausprobieren können.

Die Demo verwendet ausschließlich Core-Elemente, Creuva-Elemente und Templatevarianten
und läuft deshalb unter Contao 5.7 und 6 identisch. Die Definition liegt in
`demo/standard/demo.yaml` und lässt sich als Vorlage für weitere Demos nutzen
(neuer Ordner `demo/<name>/` → `--demo=<name>`).

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

Ein Logo für Header und Footer-Marke wird unter `header:` bzw. `brand:` mit `logo:` gesetzt,
optional mit Bildgröße (`logoSize`, wie im Modul):

```yaml
design:
  logoHeight: 4rem    # Logohöhe des Layouts (Standard 2.25rem), der Header wächst mit
header:
  logo: file:design/logo.png
  logoSize: ['', '128', 'proportional']
brand:
  logo: file:design/logo.png
```

Demo-Module lassen sich zusätzlichen Slots des Layouts zuordnen, z. B. eine Logoleiste
(Modul `creuva_logos`, Logos verlinkt über die Metadaten der Dateien) über dem Footer:

```yaml
files:
  partner/stadt.png: { alt: 'Stadt Musterstadt', link: 'https://example.org/' }
modules:
  partner: { name: 'Partner', type: 'creuva_logos', headline: 'Unsere Partner', multiSRC: ['file:partner/stadt.png'], creuvaLogosVariant: 'primary', creuvaLogosHeight: '6rem' }
layout:
  prefooter: [partner]
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
  bleibt unverändert und kann mit `--reset` beliebig oft neu importiert werden.
- Mit `filesFolder: inhalte` in der `demo.yaml` landen die Dateien stattdessen in
  `files/inhalte/` (der Ordner wird öffentlich geschaltet). Er wird von `creuva:reset` nicht
  gelöscht; bei einem erneuten Import werden die Dateien überschrieben und behalten ihre UUID.
- Den Quellordner nicht öffentlich schalten, sonst ist die `demo.yaml` im Web abrufbar.

### Website als Demo exportieren

`creuva:export` speichert eine bestehende Website als Demo-Vorlage unter `var/exports/<name>/`.
Exportiert werden der Seitenbaum mit Artikeln und Inhaltselementen, die darin verwendeten Module,
Formulare, News und Termine sowie alle referenzierten Dateien inklusive Alt-Texten:

```bash
# Startpunkt wird abgefragt (bei mehreren Websites) oder per ID übergeben
vendor/bin/contao-console creuva:export
vendor/bin/contao-console creuva:export 1 --name=kunde-x --title="Kunde X"
```

```
var/exports/kunde-x/
├── demo.yaml       IDs sind durch page:<alias>, module:<key>, file:<pfad> … ersetzt
└── files/          die verwendeten Dateien (Ordner in Galerien werden aufgelöst)
```

Die Vorlage lässt sich in jede Installation importieren: Ordner nach `files/kunde-x` kopieren
und `creuva:install --demo=files/kunde-x` ausführen (oder als Bundle-Demo nach `demo/<name>/`).

- Header-Button, Logos, Marke (Claim, Social-Media-Links) und Footer-Spalten werden aus den
  Creuva-Modulen des Seitenlayouts übernommen, Logo-Höhe, Farbschema, Variante und Eckenradius
  aus Seitenlayout und Startpunkt. Eigene Farben des Layouts werden nicht exportiert.
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

## Manuelle Einrichtung

1. **Layout → Themes → Neues Theme** anlegen.
2. **Module** anlegen:
   - *Navigation* (Navigationsmenü, 2 Ebenen) für das Hauptmenü
   - *Creuva › Creuva Header* – Logo, Navigationsmodul, CTA-Button; enthält das mobile Menü
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

4. Unter **Creuva-Design** Farbschema, Variante (Light, Dark, Editorial), Eckenradius
   sowie optional eigene Primär- und Akzentfarbe und eine Logohöhe (z. B. `4rem`, Standard
   `2.25rem`) festlegen.
5. Im **Startpunkt der Website** unter *Advanced Classes* das Set **„Creuva“** wählen.
   Redakteure können dann Hintergründe, Raster, Abstände usw. per Auswahlfeld setzen.

## Inhaltselemente & Module

| Typ | Beschreibung |
|-----|--------------|
| Creuva › Hero | Einstiegsbereich mit Dachzeile, Überschrift + hervorgehobenem Teil, Text, zwei Buttons, Bild mit organischer Maske und Badge (Bild und Text optional) |
| Creuva › Creuva Header | Logo, Hauptnavigation (mit Dropdowns), CTA-Button, mobiles Menü |
| Creuva › Creuva Marke | Logo, Claim, Social-Media-Links (Plattform wird anhand der URL erkannt) |

Gestaltet werden außerdem die Contao-Standardelemente (Text, Liste, Beschreibungsliste,
Tabelle, Bild, Galerie, Akkordeon, Downloads, Hyperlink, Formulare, Breadcrumb), die
News-Module (Liste als Karten, Leser, Archivmenü, Seitenzahlen) und die Elemente des
Theme Components Bundles 2.0 (Contentbox, Teaserbox, Preisbox, Feature-Element, Simplebox,
Icon-Tile, Modal).

### Templatevarianten

Unter *Template-Einstellungen › Individuelles Template* stehen diese Varianten zur Wahl:

| Element | Variante | Verwendung |
|---------|----------|------------|
| Text | `creuva_card` | Karte mit Bild, Kategorie (Subline), Titel und Text; der letzte Link macht die ganze Karte klickbar |
| Text | `creuva_feature` | Icon-Box mit kleinem Bild oder SVG-Icon |
| Text | `creuva_testimonial` | Kundenstimme: Zitat im Text, Name als Überschrift, Funktion als Subline, Porträt als Bild |
| Text | `creuva_team` | Teammitglied: Porträt, Name, Funktion (Subline), Kontakt |
| Text | `creuva_price` | Preis-Karte: Paket als Überschrift, Preis als Subline, Leistungsliste, letzter Link als Button; mit `u-featured` hervorgehoben |
| Beschreibungsliste | `creuva_stats` | Kennzahlen: Begriff = Zahl, Beschreibung = Bezeichnung |
| Beschreibungsliste | `creuva_timeline` | Zeitleiste: Begriff = Jahr/Schritt, Beschreibung = Ereignis |

In Kästen mit `u-bg-primary` oder `u-bg-accent` wird ein Link, der allein im letzten
Absatz steht, automatisch als Button dargestellt (Call-to-Action-Box).

### Abschnitte mit Hintergrund

Die Hintergrundklassen (`u-bg-muted`, `u-bg-accent`, `u-bg-primary`) können auch im
**Artikel** unter *Experten-Einstellungen › CSS-Klasse* eingetragen werden – der Artikel
wird dann zum Abschnitt mit Hintergrund über die volle Breite.

### Überschriften mit Dachzeile oder Akzent

Das Theme Components Bundle erweitert Überschriften um eine *Subline*:

- Subline **ohne** „Inline“ → kleine Dachzeile über der Überschrift („WAS WIR TUN“)
- Subline **mit** „Inline“ → zweifarbige Überschrift, die eigentliche Überschrift
  erscheint in der Akzentfarbe („Mehr als nur eine **Agentur.**“)

### Klassen-Set (Advanced Classes)

| Bereich | Klassen |
|---------|---------|
| Hintergrund | `u-bg-surface`, `u-bg-muted`, `u-bg-accent`, `u-bg-primary` |
| Breite | `u-full-bleed` (Hintergrund über die volle Breite), `u-narrow` |
| Rahmen | `u-card`, `u-featured` (hervorgehobene Preis-Karte) |
| Raster (Elementgruppe) | `u-grid-2`, `u-grid-3`, `u-grid-4` |
| Abstände | `u-py-0` … `u-py-xl`, `u-mt-0` … `u-mt-xl` |
| Text | `u-text-left/center/right`, `u-lead`, `u-muted`, `u-accent`, `u-kicker` |
| Links | `u-button`, `u-button-secondary` (für das Hyperlink-Element) |
| Sichtbarkeit | `u-hide-mobile`, `u-hide-desktop` |

Die Klassen funktionieren auch ohne das Advanced Classes Bundle über das Feld
*CSS-Klasse*.

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
WCAG-Kontraste (Text 4,5:1, große Schrift 3:1) für Light und Dark und bricht mit einer
Meldung ab, wenn ein Schema sie nicht erreicht. Das Schema `default` gilt ohne Auswahl.

Im Seitenlayout werden die Schemen als Farbpunkte zur Auswahl angeboten; darunter zeigt eine
Live-Vorschau Überschrift, Buttons und Boxen im gewählten Schema – inklusive Variante,
Eckenradius und eigener Farben (`public/backend.css` und `backend.js`, nur im Seitenlayout geladen).

Das Seitenlayout gibt das Schema als `data-scheme` am `<html>`-Element aus; eigene
Primär-/Akzentfarben aus dem Layout überschreiben es.

Im Startpunkt der Website (Root-Seite) lassen sich Farbschema, Variante und Eckenradius
unter „Creuva-Design“ zusätzlich festlegen. Diese Werte haben Vorrang vor dem Seitenlayout;
ein Schema im Startpunkt ersetzt auch die eigenen Farben des Layouts. So können mehrere
Websites ein Layout nutzen und trotzdem unterschiedlich aussehen. Die frühere Variante „Corporate“
ist jetzt das Schema „Blau“ (bestehende Layouts werden per Migration umgestellt).

### Style-Switcher

Das Frontend-Modul *Creuva Style-Switcher* blendet am linken Rand ein Zahnrad ein, über das
Besucher Farbschema, Variante und Eckenradius wechseln. Die Auswahl wird nur im Browser
(localStorage) gespeichert und gilt auf allen Seiten mit dem Modul. Per URL lässt sich eine
Kombination direkt verlinken, z. B. `?scheme=red&theme=dark&radius=none`. Das Modul im Slot
`top` platzieren, damit die Auswahl vor dem ersten Rendern greift.

In der Frontend-Vorschau zeigt der Switcher Administratoren zusätzlich den Button
„Für die Website speichern“. Er speichert die Auswahl im Startpunkt der aktuellen Website,
leert den Seiten-Cache dieser Website und gilt danach für alle Besucher. Redakteure ohne
Admin-Rechte und normale Besucher sehen den Button nicht; der Endpunkt
(`/contao/creuva/style-switcher`) prüft Admin-Rechte und Request-Token.

Mit der Moduloption „Nur für Administratoren in der Vorschau“ ist der Switcher für Besucher
unsichtbar – so lässt sich das Design einer Live-Website in der Vorschau auswählen.

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
config/            Symfony-Services, Farbschemen (color-schemes.json)
contao/config/     Registrierung des Klassen-Sets
contao/dca/        DCA-Erweiterungen (tl_layout, tl_content, tl_module)
contao/languages/  Übersetzungen (de, en)
contao/templates/  Twig-Templates (Seitenlayout, Elemente, Templatevarianten, Module, Komponenten)
demo/              Demo-Definitionen (demo.yaml) und Demo-Dateien
public/            gebaute Assets + sets/creuva.json → /bundles/creuvatheme/
src/               PHP (Bundle, Manager-Plugin, Controller, DCA-Listener, Installer + Befehle creuva:install, creuva:reset)
theme-idee/        Konzept und Entwürfe
```

## Bekannte Einschränkungen

- Die Barrierefreiheits-Werkzeuge (Kontrast, Schriftgröße) und die Animationen beim
  Scrollen des Theme Components Bundles nutzen die Hooks `generatePage` bzw.
  `parseFrontendTemplate`, die Contao bei Layouts vom Typ „Modern“ nicht ausführt.
  Die Einstellungen im Startpunkt haben deshalb im Creuva-Layout keine Wirkung.

## Lizenz

Proprietär – © pdir GmbH, siehe [LICENSE](LICENSE).
Hinweise zu Bibliotheken und Schriften Dritter: [THIRD-PARTY-NOTICES.md](THIRD-PARTY-NOTICES.md).
