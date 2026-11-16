# Changelog

[//]: <> (
Types of changes
    Added for new features.
    Changed for changes in existing functionality.
    Deprecated for soon-to-be removed features.
    Removed for now removed features.
    Fixed for any bug fixes.
    Security in case of vulnerabilities.
)

## [1.1.0](https://github.com/pdir/creuva-theme-bundle/tree/1.1.0) – 2026-11-12

- [Added] Hero badge: appearances checklist, info, label, text, key figure and rating (stars), position (corners and middle of the edges), small headline, icon, link and note points ("!")

## [1.0.0](https://github.com/pdir/creuva-theme-bundle/tree/1.0.0) – 2026-11-09

Initial release. Requires Contao 5.7 (PHP 8.3+) or Contao 6 (PHP 8.4+), the Theme Components Bundle 2.0 and the CSS Style Selector Bundle 2.1.

**Layout and design**

- [Added] Page layout of the type "Modern" with the Twig template `page/layout/creuva`, the same in Contao 5.7 and 6
- [Added] 17 color schemes from `config/color-schemes.json`, shown as tiles in the page layout; the build checks the WCAG 2.1 AA contrast of every scheme in light and dark mode
- [Added] Color modes light, dark and "system" (follows the operating system without flashing), typographies "Modern" (Plus Jakarta Sans) and "Editorial" (Fraunces) and four corner radii
- [Added] Design settings of the page layout can be overridden per website root
- [Added] Style switcher module to try out color scheme, color mode, typography and corner radius; administrators can save the selection for the website in the front end preview
- [Added] Self-hosted variable web fonts, no requests to Google
- [Added] Scroll animations (all sections or only with a CSS class) and hover effects
- [Added] Print styles: content only, black on white, link addresses after the links
- [Added] SVG icons are colored with the color scheme
- [Added] SVG favicon next to the favicon of the website root

**Content elements and modules**

- [Added] Content elements Hero (several image shapes, badge as a card), Logo bar and Match
- [Added] Front end modules Header (logo, main navigation with dropdowns, call to action, mobile menu, search), Brand, Logo bar and Style switcher
- [Added] Template variants for text (card, icon box, call to action, image and text, testimonial, team member, price card), description lists (key figures counting up, timeline, facts) and galleries (slider)
- [Added] Styles for the Contao core elements, news, events, FAQ, newsletter, comments, listing and search modules and the elements of the Theme Components Bundle 2.0
- [Added] Content slider with elements per view, arrows, dots and thumbnails
- [Added] News reader with a header like the hero, optional accent headline and reading time
- [Added] Responsive gallery images: the gallery template sets the `sizes` of every image for its tile
- [Added] The first large image of a page is loaded with high fetch priority
- [Added] CSS classes as categories and groups of the CSS Style Selector (colors, spacing, grids, animations, …), missing ones are added with `creuva:css-styles`
- [Added] Select fields styled without JavaScript

**Search engines and link preview**

- [Added] Open Graph tags and `twitter:card` on all indexable pages: news readers show the news as an article with its image and dates, other pages the first image of the main section or the preview image of the website root
- [Added] Preview image for social networks in the website root
- [Added] Canonical link enabled for new website roots, only the main section is added to the search index

**Installation and demos**

- [Added] `creuva:install` creates the theme, the modules and the page layout and assigns it to a website root; optionally with basic pages (`--with-pages`) or a demo (`--demo`)
- [Added] Four demos: Standard / Creative, Company, Portfolio / Studio and Sports club, each with pages, images, news, events, forms and an accessibility statement
- [Added] Demos from a folder in the file manager (`--demo=files/my-demo`) with the same syntax as the demos of the bundle
- [Added] `creuva:export` exports a website as a demo, including files, image sizes and the design settings
- [Added] `creuva:reset` deletes all contents (with a database backup), `creuva:install --replace` replaces a single website with the same domain and language in a multisite installation
- [Added] `creuva:styleguide` adds a styleguide page with all elements and variants
- [Added] Back end page for the theme setup and the demo import
- [Added] Module names and texts in German or English, depending on the language of the website root
