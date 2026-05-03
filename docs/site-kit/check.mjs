import { access, readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { dirname } from 'node:path';

const root = join(dirname(fileURLToPath(import.meta.url)), '..', '..');
const outputDir = join(root, 'docs', 'site');

const requiredFiles = [
  'index.html',
  'assets/site.css',
  'assets/site.js',
  'assets/brand/gjallr-mark.svg',
  'assets/brand/gjallr-logo.svg',
  'assets/brand/favicon.svg',
];

const checks = [];

const assert = (condition, message) => {
  checks.push({ ok: Boolean(condition), message });
};

const run = async () => {
  await Promise.all(requiredFiles.map((file) => access(join(outputDir, file))));

  const html = await readFile(join(outputDir, 'index.html'), 'utf8');
  const css = await readFile(join(outputDir, 'assets', 'site.css'), 'utf8');
  const js = await readFile(join(outputDir, 'assets', 'site.js'), 'utf8');

  assert(html.includes('href="/gjallr/assets/site.css"'), 'CSS uses the GitHub Pages base path.');
  assert(html.includes('src="/gjallr/assets/site.js"'), 'JS uses the GitHub Pages base path.');
  assert(html.includes('href="/gjallr/assets/brand/favicon.svg"'), 'Favicon uses the GitHub Pages base path.');
  assert(html.includes('href="#saves"') && html.includes('id="saves"'), 'Navigation anchor for saves exists.');
  assert(html.includes('href="#pipeline"') && html.includes('id="pipeline"'), 'Navigation anchor for pipeline exists.');
  assert(html.includes('href="#skills"') && html.includes('id="skills"'), 'Navigation anchor for skills exists.');
  assert(html.includes('href="#ai-handoff"') && html.includes('id="ai-handoff"'), 'Navigation anchor for AI handoff exists.');
  assert(html.includes('href="#showcase"') && html.includes('id="showcase"'), 'Navigation anchor for showcase exists.');
  assert(html.includes('https://github.com/odinns/gjallr'), 'External links point to the GitHub repo.');
  assert(html.includes('php artisan gjallr:wayback:recover-media --dry-run --limit=50'), 'Wayback terminal specimen is present.');
  assert(html.includes('frontend-capable AI'), 'AI-assisted rebuild handoff copy is present.');
  assert(html.includes("Daddy&#39;s Birthday"), 'Daddy Birthday motivating example is present.');
  assert(html.includes('Wayback snapshot') || html.includes('Archived reference'), 'Wayback comparison showcase copy is present.');
  assert(css.includes('@media (prefers-reduced-motion: reduce)'), 'Reduced-motion CSS is present.');
  assert(js.includes('prefers-reduced-motion: reduce'), 'Reduced-motion JS guard is present.');
  assert(!html.includes('{{'), 'Generated HTML has no leftover template tokens.');

  const failed = checks.filter((check) => !check.ok);

  checks.forEach((check) => {
    console.log(`${check.ok ? 'ok' : 'fail'} - ${check.message}`);
  });

  if (failed.length > 0) {
    process.exitCode = 1;
  }
};

run().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
