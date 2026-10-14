import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';

const outDir = resolve(import.meta.dirname, 'public');

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
  plugins: [symfonyManifest()],
  build: {
    outDir,
    emptyOutDir: false, // public/ also holds static files (e.g. sets/)
    sourcemap: mode === 'development',
    rolldownOptions: {
      input: { creuva: resolve(import.meta.dirname, 'assets/js/creuva.js') },
      output: {
        entryFileNames: '[name].js',
        assetFileNames: '[name][extname]',
      },
    },
  },
}));
