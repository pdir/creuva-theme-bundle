<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.de>
 */

$GLOBALS['TL_LANG']['tl_layout']['creuva_legend'] = 'Creuva-Design';
$GLOBALS['TL_LANG']['tl_layout']['creuvaScheme'] = ['Farbschema', 'Primär- und Akzentfarbe des Themes.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaVariant'] = ['Designvariante', 'Grundstimmung des Themes.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaVariant_options'] = ['light' => 'Light / Minimal', 'dark' => 'Dark / Creative', 'auto' => 'Light oder Dark nach Systemeinstellung', 'editorial' => 'Editorial'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaRadius'] = ['Eckenradius', 'Rundung von Karten, Buttons und Bildern.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaRadius_options'] = ['none' => 'Eckig', 'small' => 'Klein', 'medium' => 'Mittel', 'large' => 'Groß'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaPrimaryColor'] = ['Primärfarbe', 'Überschreibt die Primärfarbe des Farbschemas (Hex-Wert ohne #).'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaAccentColor'] = ['Akzentfarbe', 'Überschreibt die Akzentfarbe des Farbschemas (Hex-Wert ohne #). Sie wird auch für Links und kleine Texte genutzt und braucht einen Kontrast von mindestens 4,5:1 zu Weiß.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaLogoHeight'] = ['Logohöhe', 'Höhe des Logos im Header als CSS-Wert, z. B. „4rem“ (Standard: 2.25rem). Der Header wächst bei Bedarf mit.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaStylesheets'] = ['Eigene Stylesheets', 'CSS-Dateien aus der Dateiverwaltung für individuelle Anpassungen, z. B. „files/design/custom.css“. Sie werden nach dem Creuva-CSS geladen und haben ohne Cascade-Layer immer Vorrang.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaAnimations'] = ['Animationen', 'Elemente blenden beim Scrollen sanft ein, Zahlen zählen hoch. Mit „Nur per CSS-Klasse“ werden nur Elemente mit einer Klasse aus „Animation“ (CSS-Stile) animiert. Besucher mit der Systemeinstellung „Bewegung reduzieren“ sehen keine Animationen.'];
$GLOBALS['TL_LANG']['tl_layout']['creuvaAnimations_options'] = ['auto' => 'Alle Abschnitte', 'classes' => 'Nur per CSS-Klasse', 'none' => 'Keine'];
