<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_LANG']['creuva_installer'] = [
    'intro' => 'Hier richtest du das Theme ein und importierst Demos. Jede Aktion entspricht einem Konsolenbefehl, beide Wege führen zum gleichen Ergebnis. Farbschema, Farbmodus und Schrift passt du danach im Seitenlayout an.',
    'missing_columns' => 'Die Datenbank ist nicht aktuell (fehlend: %s). Bitte zuerst die Datenbank im Contao Manager aktualisieren oder „contao:migrate“ ausführen.',
    'success' => 'Erledigt.',
    'error' => 'Die Aktion ist fehlgeschlagen. Details stehen in der Ausgabe.',
    'command' => 'Entspricht dem Konsolenbefehl',
    'output' => 'Ausgabe',
    'install_legend' => 'Theme einrichten',
    'mode' => ['Was soll eingerichtet werden?', ''],
    'mode_demo' => ['Demo-Website importieren', 'Neuer Startpunkt mit allen Seiten, Inhalten, News, Bildern und Formularen einer Demo.'],
    'mode_pages' => ['Leere Seitenstruktur anlegen', 'Neuer Startpunkt mit Startseite, Kontakt, Impressum, Datenschutz, Sitemap und Suche.'],
    'mode_roots' => ['Bestehende Website verwenden', 'Das Theme wird den ausgewählten Startpunkten zugewiesen, die Inhalte bleiben unverändert.'],
    'mode_theme' => ['Nur das Theme anlegen', 'Theme, Module und Seitenlayout anlegen, das Layout später selbst zuweisen.'],
    'demo' => ['Demo', 'Eine der mitgelieferten Demos.'],
    'demo_folder' => ['Oder eigener Ordner', 'Ordner mit einer demo.yaml in der Dateiverwaltung, z. B. „files/meine-demo“. Hat Vorrang vor der Auswahl.'],
    'roots' => ['Startpunkte', 'Diese Websites verwenden danach das Creuva-Layout. Ein vorhandenes Layout wird ersetzt.'],
    'no_roots' => 'Es gibt noch keinen Startpunkt.',
    'site_title' => ['Titel der Website', 'Seitentitel des neuen Startpunkts.'],
    'language' => ['Sprache', 'Sprache des neuen Startpunkts, z. B. „de“ oder „en“.'],
    'dns' => ['Domain', 'Optional. Nötig, wenn es schon einen Startpunkt mit derselben Sprache ohne Domain gibt.'],
    'theme_name' => ['Name des Themes', 'Ein Theme mit diesem Namen darf noch nicht existieren.'],
    'reset' => ['Vorher alle Inhalte löschen', 'Leert Seitenstruktur, Artikel, Themes, Module, Formulare, News usw., z. B. um eine Demo neu zu importieren. Benutzer, Mitglieder und Dateien bleiben erhalten.'],
    'no_backup' => ['Ohne Datenbank-Backup', 'Standardmäßig wird vor dem Löschen ein Backup angelegt (wiederherstellen mit „contao:backup:restore“).'],
    'install_submit' => 'Einrichten',
    'styleguide_legend' => 'Styleguide',
    'styleguide_intro' => 'Fügt einer Website die versteckte Seite „Styleguide“ mit allen Elementen, Template-Varianten, CSS-Klassen und Modulen hinzu.',
    'styleguide_root' => ['Website', 'Startpunkt, der das Creuva-Layout verwendet.'],
    'styleguide_replace' => ['Vorhandenen Styleguide ersetzen', 'Zum Beispiel nach einem Update des Themes.'],
    'styleguide_submit' => 'Styleguide hinzufügen',
    'css_styles_legend' => 'CSS-Styles',
    'css_styles_intro' => 'Ergänzt fehlende Creuva-Gruppen und -Styles im CSS-Style-Selector, z. B. nach einem Update mit neuen Klassen. Eigene Änderungen bleiben erhalten.',
    'css_styles_submit' => 'CSS-Styles ergänzen',
    'running' => 'Bitte warten …',
];
