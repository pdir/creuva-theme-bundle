<?php

declare(strict_types=1);

/*
 * Creuva theme bundle for Contao Open Source CMS
 *
 * Copyright (C) pdir GmbH <develop@pdir.dev>
 */

$GLOBALS['TL_LANG']['tl_content']['creuvaKicker'] = ['Dachzeile', 'Kurzer Text in Großbuchstaben über der Überschrift, z. B. „Kreative Partner auf Augenhöhe“. Lässt sich mit einer zweifarbigen Überschrift kombinieren.'];

// Fields of the theme components bundle, named after their effect in Creuva
$GLOBALS['TL_LANG']['tl_content']['subline'] = ['Zusatz zur Überschrift', 'Ohne Häkchen: kleine Zeile über der Überschrift. „Zweifarbige Überschrift“: Zusatz und Überschrift in einer Zeile, der Zusatz normal, die Überschrift in der Akzentfarbe – z. B. Zusatz „Mehr als nur eine“, Überschrift „Agentur.“. „Darunter“: Unterzeile unter der Überschrift.'];
$GLOBALS['TL_LANG']['tl_content']['headline_inline'] = ['Zweifarbige Überschrift', 'Zusatz und Überschrift nebeneinander, die Überschrift in der Akzentfarbe.'];
$GLOBALS['TL_LANG']['tl_content']['subheadline'] = ['Zusatz darunter anzeigen', 'Den Zusatz als Unterzeile unter der Überschrift anzeigen.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaIcons'] = ['Icons (Kennzahlen)', 'Optionale Icons über den Zahlen der Templatevariante „Kennzahlen“, in der Reihenfolge der Einträge (z. B. einfarbige SVG-Icons).'];
$GLOBALS['TL_LANG']['tl_content']['creuva_badge_legend'] = 'Badge';
$GLOBALS['TL_LANG']['tl_content']['creuvaBadge'] = ['Badge-Text', 'Kleine Karte auf dem Bild. Liste: eine Zeile pro Punkt, z. B. „Kreativ“, „Strategisch“, „Zukunftssicher“; ein „!“ am Zeilenanfang zeigt statt des Icons ein Ausrufezeichen (Hinweis). Info: erste Zeile fett, weitere Zeilen klein darunter. Kennzeichnung: erste Zeile klein, zweite Zeile fett. Text: alle Zeilen als normaler Text. Kennzahl: erste Zeile als große Zahl, z. B. „250+“, darunter die Beschriftung. Bewertung: erste Zeile ist der Wert, z. B. „4,9“ (die Sterne werden danach gefüllt), darunter die Quelle. Bei „Vollbild“ erscheint der Text als Bildunterschrift.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeStyle'] = ['Darstellung', 'Liste: Punkte mit Icon (bisherige Darstellung). Checkliste: Icons in einem Kreis. Info: großes Icon, fette Zeile, Text darunter (z. B. Standort, Dokumentation). Kennzeichnung: großes Icon, kleine Zeile, fette Zeile darunter (z. B. „Antwort in der Regel“ / „innerhalb von 24 Std.“). Text: normaler Text, jede Zeile ein Absatz, optional mit Icon und Überschrift (z. B. ein kurzes Zitat oder ein Hinweis). Kennzahl: große Zahl in der Akzentfarbe mit Beschriftung (z. B. „250+“ / „umgesetzte Projekte“). Bewertung: fünf Sterne, gefüllt nach dem Wert der ersten Zeile, daneben der Wert, darunter die Quelle (z. B. „4,9“ / „aus 120 Google-Bewertungen“).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeStyle_options'] = ['list' => 'Liste', 'checklist' => 'Checkliste (Icons im Kreis)', 'info' => 'Info (Icon, Titel, Text)', 'label' => 'Kennzeichnung (Icon, Hinweis, Wert)', 'text' => 'Text', 'stat' => 'Kennzahl (Zahl, Beschriftung)', 'rating' => 'Bewertung (Sterne, Wert, Quelle)'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgePosition'] = ['Position', 'Stelle am Bildrand, an der das Badge steht. Auf großen Bildschirmen ragt es seitlich (links/rechts) bzw. nach unten/oben (Mitte) über das Bild hinaus.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgePosition_options'] = ['bottom-start' => 'Unten links', 'bottom-center' => 'Unten Mitte', 'bottom-end' => 'Unten rechts', 'center-start' => 'Mitte links', 'center-end' => 'Mitte rechts', 'top-start' => 'Oben links', 'top-center' => 'Oben Mitte', 'top-end' => 'Oben rechts'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeHeadline'] = ['Badge-Überschrift', 'Optionale kleine Zeile in Großbuchstaben über dem Text, z. B. „Contao-Partner“ oder „Ergebnis“.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeIcon'] = ['Icon', 'Liste und Checkliste: Icon vor jedem Punkt (Standard: Häkchen). Alle anderen Darstellungen: großes Icon links (Standard: keins).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeIcon_options'] = ['check' => 'Häkchen', 'clock' => 'Uhr', 'pin' => 'Standort', 'file' => 'Dokument', 'info' => 'Info', 'chat' => 'Sprechblase', 'download' => 'Download', 'shield' => 'Schild', 'star' => 'Stern', 'mail' => 'Brief', 'phone' => 'Telefon'];
$GLOBALS['TL_LANG']['tl_content']['creuvaBadgeUrl'] = ['Badge-Link', 'Optional: Das ganze Badge wird zum Link, z. B. auf die Kontaktseite oder die Dokumentation.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeroMask'] = ['Bildform', 'Organisch: Bild in 4:3 mit organischer Maske. Abgerundet: ganzes Bild mit runden Ecken, die Akzentform liegt dahinter. Bild unverändert: ohne Form, Fläche und Beschnitt. Bild unverändert mit Akzentform: ohne Schatten und Beschnitt, die Akzentform liegt dahinter (ideal für freigestellte Bilder mit transparentem Hintergrund). Vollbild: das Bild füllt den ganzen Bereich, der Text steht hell darauf (Bildgröße im Querformat wählen, der Badge-Text erscheint als Bildunterschrift).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeroMask_options'] = ['organic' => 'Organisch', 'rounded' => 'Abgerundet', 'none' => 'Bild unverändert', 'blob' => 'Bild unverändert mit Akzentform', 'cover' => 'Vollbild (Text auf dem Bild)'];
$GLOBALS['TL_LANG']['tl_content']['creuvaUrl1'] = ['Link primärer Button', 'Ziel des ersten Buttons.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaLinkTitle1'] = ['Text primärer Button', 'Beschriftung des ersten Buttons.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaUrl2'] = ['Link sekundärer Button', 'Ziel des zweiten Buttons.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaLinkTitle2'] = ['Text sekundärer Button', 'Beschriftung des zweiten Buttons.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHeadlineAccent'] = ['Hervorgehobener Teil', 'Wird in der Akzentfarbe an die Überschrift angehängt, z. B. „begeistern.“'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderView'] = ['Elemente pro Ansicht', 'Wie viele Elemente nebeneinander zu sehen sind (auf Tablets höchstens zwei, auf Smartphones eines).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderView_options'] = ['1' => '1 Element', '2' => '2 Elemente', '3' => '3 Elemente', '4' => '4 Elemente'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderArrows'] = ['Navigationspfeile', 'Pfeile zum Blättern unter dem Slider anzeigen.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderDots'] = ['Punkte', 'Einen Punkt pro Element unter dem Slider anzeigen.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaSliderThumbs'] = ['Thumbnails', 'Vorschaubilder unter dem Slider anzeigen (aus dem ersten Bild jedes Elements), z. B. für eine Bildergalerie.'];
$GLOBALS['TL_LANG']['tl_content']['creuva_logos_legend'] = 'Darstellung';
$GLOBALS['TL_LANG']['tl_content']['creuvaLogosHeight'] = ['Logohöhe', 'Höhe der Logos als CSS-Wert, z. B. „4rem“ (Standard: 5rem). Die Breite darf das Vierfache betragen (Wortmarken); die Bildgröße sollte groß genug gewählt sein.'];
$GLOBALS['TL_LANG']['tl_content']['creuva_match_legend'] = 'Spiel';
$GLOBALS['TL_LANG']['tl_content']['creuvaHomeTeam'] = ['Heimmannschaft', 'Name der Heimmannschaft.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaHomeLogo'] = ['Wappen Heim', 'Wappen oder Logo der Heimmannschaft (SVG empfohlen).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaAwayTeam'] = ['Gastmannschaft', 'Name der Gastmannschaft.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaAwayLogo'] = ['Wappen Gast', 'Wappen oder Logo der Gastmannschaft (SVG empfohlen).'];
$GLOBALS['TL_LANG']['tl_content']['creuvaMatchDate'] = ['Anstoß', 'Datum und Uhrzeit des Spiels.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaVenue'] = ['Spielort', 'Z. B. „Sportpark am Mühlbach, Rasenplatz“.'];
$GLOBALS['TL_LANG']['tl_content']['creuvaScore'] = ['Ergebnis', 'Z. B. „3:1“. Leer lassen für ein kommendes Spiel, dann erscheint die Anstoßzeit. Auch Text wie „abgesagt“ ist möglich.'];
