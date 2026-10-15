import { createHash } from 'node:crypto';
import { existsSync, readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import { checkScheme, loadSchemes, toScss } from './assets/build/color-schemes.js';

const outDir = resolve(import.meta.dirname, 'public');
const schemesFile = resolve(import.meta.dirname, 'config/color-schemes.json');
const schemeDataFile = resolve(import.meta.dirname, 'assets/scss/base/_scheme-data.scss');

/**
 * Turns config/color-schemes.json into the SCSS map of _schemes.scss and fails
 * the build if a scheme does not reach the WCAG contrast ratios.
 */
const colorSchemes = () => ({
  name: 'creuva-color-schemes',
  buildStart() {
    this.addWatchFile(schemesFile);

    const data = loadSchemes(schemesFile);

    if (!data.schemes?.[data.default]) {
      this.error(`The default color scheme "${data.default}" is not defined in config/color-schemes.json.`);
    }

    const errors = Object.entries(data.schemes).flatMap(([name, scheme]) => checkScheme(name, scheme));

    if (errors.length) {
      this.error(`Insufficient contrast in config/color-schemes.json:\n  ${errors.join('\n  ')}`);
    }

    const scss = toScss(data);

    if (!existsSync(schemeDataFile) || readFileSync(schemeDataFile, 'utf8') !== scss) {
      writeFileSync(schemeDataFile, scss);
    }
  },
});

/**
 * Writes a Symfony-compatible manifest.json ({"creuva.css": "creuva.css?v=<hash>"}).
 * Contao picks it up automatically for the "creuva_theme" asset package, so
 * {{ asset('creuva.css', 'creuva_theme') }} gets a cache-busting query string.
 */
const symfonyManifest = () => ({
  name: 'symfony-manifest',
  writeBundle(_options, bundle) {
    const manifest = {};

    for (const fileName of Object.keys(bundle).filter((name) => !name.endsWith(".map")).sort()) {
      const hash = createHash('md5').update(readFileSync(resolve(outDir, fileName))).digest('hex').slice(0, 8);
      manifest[fileName] = `${fileName}?v=${hash}`;
    }

    writeFileSync(resolve(outDir, 'manifest.json'), `${JSON.stringify(manifest, null, 2)}\n`);
  },
});

export default defineConfig(({ mode }) => ({
  publicDir: false,
  plugins: [colorSchemes(), symfonyManifest()],
  build: {
    outDir,
    emptyOutDir: false, // public/ also holds static files (e.g. sets/)
    sourcemap: mode === 'development',
    rolldownOptions: {
      input: {
        creuva: resolve(import.meta.dirname, 'assets/js/creuva.js'),
        backend: resolve(import.meta.dirname, 'assets/js/backend.js'),
      },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
}));
