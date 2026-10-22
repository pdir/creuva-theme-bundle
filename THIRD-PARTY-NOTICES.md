# Third-Party Notices – Creuva

Creuva selbst steht unter der pdir Contao Theme Licence (siehe `LICENSE`). Die folgenden
Bestandteile Dritter werden vom Theme vorausgesetzt, beim Bauen verwendet oder referenziert
und unterliegen ausschließlich ihren eigenen Lizenzbedingungen.

Creuva is licensed under the pdir Contao Theme Licence (see `LICENSE`). The third-party
components listed below are required, used at build time or referenced by the theme and are
subject to their own license terms only.

## Laufzeit-Abhängigkeiten (Composer) / Runtime dependencies

Diese Pakete sind **nicht** im Theme enthalten, sondern werden von Composer bzw. dem
Contao Manager installiert. / Not shipped with the theme, installed via Composer.

| Paket / Package                                  | Lizenz / License   | Projekt / Project                                   |
|--------------------------------------------------|--------------------|-----------------------------------------------------|
| contao/core-bundle                               | LGPL-3.0-or-later  | https://github.com/contao/contao                    |
| symfony/* (config, console, dependency-injection, finder, http-foundation, http-kernel, yaml) | MIT | https://symfony.com |
| doctrine/dbal                                    | MIT                | https://github.com/doctrine/dbal                    |
| contao-themes-net/theme-components-bundle        | proprietär / proprietary | https://contao-themes.net                     |
| contao-themes-net/advanced-classes-bundle (indirekt / transitive) | LGPL-3.0-or-later | https://github.com/contao-themes-net/advanced-classes-bundle |

## Build-Werkzeuge (npm) / Build tools

Nur zum Kompilieren von `public/creuva.css` und `public/creuva.js` verwendet; im
ausgelieferten Code ist kein Quellcode dieser Pakete enthalten. / Used to compile the
assets only; no code of these packages is part of the distributed files.

| Paket / Package | Lizenz / License | Projekt / Project                    |
|-----------------|------------------|--------------------------------------|
| vite            | MIT              | https://github.com/vitejs/vite       |
| sass            | MIT              | https://github.com/sass/dart-sass    |

## Schriften / Fonts

Die Schriften werden mit dem Theme ausgeliefert und selbst gehostet (keine Anfragen an Dritte):
variable WOFF2-Dateien mit dem Zeichensatz Latin aus Google Fonts unter `assets/fonts/`, beim
Bauen nach `public/` kopiert. Es gilt die SIL Open Font License 1.1, die Lizenzdateien liegen
daneben (`assets/fonts/OFL-*.txt`). / The fonts are shipped and self-hosted (variable WOFF2,
latin subset of Google Fonts, `assets/fonts/`, copied to `public/` by the build). They are
licensed under the SIL Open Font License 1.1, see `assets/fonts/OFL-*.txt`.

| Schrift / Font    | Datei / File                    | Lizenz / License | Copyright / Quelle / Source |
|-------------------|---------------------------------|------------------|-----------------------------|
| Plus Jakarta Sans | `plus-jakarta-sans.woff2` (200–800) | OFL-1.1 | © 2020 The Plus Jakarta Sans Project Authors – https://github.com/tokotype/PlusJakartaSans |
| Fraunces          | `fraunces.woff2` (400–700, nur Variante „Editorial“) | OFL-1.1 | © 2018 The Fraunces Project Authors – https://github.com/undercasetype/Fraunces |

## Demo-Inhalte / Demo content

Texte, Bilder, Icons und PDF-Dateien unter `demo/` und `assets/demo/` dienen nur der
Darstellung der Demo-Seite und dürfen nicht für eigene Projekte verwendet werden
(siehe `LICENSE`, Punkt 2). / Demo texts, images, icons and PDFs are for demonstration
purposes only and must not be used in your own projects (see `LICENSE`, section 2).
