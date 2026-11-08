import { exec } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';
import path from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const repoRoot = fileURLToPath(new URL('../', import.meta.url));
const baselinePath = new URL('../resources/js/Types/typecheck-baseline.json', import.meta.url);
const posix = (value) => value.replaceAll('\\', '/');

export function parse(output) {
  const diagnostics = [];
  const unparsed = [];
  let previous;
  for (const line of output.replace(/\u001b\[[0-9;]*m/g, '').split(/\r?\n/)) {
    if (/^npm (?:notice|warn)(?:\s|$)/.test(line)) continue;
    const match = /^(.*?)\((\d+),(\d+)\): error (TS\d+): (.*)$/.exec(line);
    if (match) {
      previous = { file: match[1], line: Number(match[2]), column: Number(match[3]), code: match[4], message: match[5] };
      diagnostics.push(previous);
    } else if (/\berror\s+TS\d+\b|^\s*(?:error\b|(?:TypeError|Error|ReferenceError|SyntaxError):|FATAL ERROR)/i.test(line)) {
      unparsed.push(line);
      previous = undefined;
    } else if (/^\s+\S/.test(line) && previous) {
      previous.message += `\n${line}`;
    } else if (line.trim()) {
      unparsed.push(line);
      previous = undefined;
    }
  }
  return { diagnostics, unparsed };
}

export function fingerprint(diagnostic, root = repoRoot) {
  const prefix = `${posix(root).replace(/\/$/, '')}/`;
  let file = posix(diagnostic.file);
  if (file.startsWith(prefix)) file = file.slice(prefix.length);
  file = path.posix.normalize(file);
  const message = posix(diagnostic.message.split(/\r?\n/)[0])
    .replaceAll(prefix, '').replace(/\s+/g, ' ').trim();
  return `${file} | ${diagnostic.code} | ${message}`;
}

export function compare(current, baseline) {
  const increased = [];
  const decreased = [];
  const stale = [];
  for (const key of [...new Set([...Object.keys(current), ...Object.keys(baseline)])].sort()) {
    const entry = { fingerprint: key, current: current[key] ?? 0, baseline: baseline[key] ?? 0 };
    if (entry.current > entry.baseline) increased.push(entry);
    if (entry.current < entry.baseline) decreased.push(entry);
    if (entry.current === 0 && entry.baseline > 0) stale.push(entry);
  }
  return { increased, decreased, stale };
}

function readBaseline() {
  const baseline = JSON.parse(readFileSync(baselinePath, 'utf8'));
  if (baseline.version !== 1 || !baseline.fingerprints || Array.isArray(baseline.fingerprints)
    || typeof baseline.fingerprints !== 'object'
    || Object.values(baseline.fingerprints).some((count) => !Number.isSafeInteger(count) || count <= 0)
    || baseline.total !== Object.values(baseline.fingerprints).reduce((sum, count) => sum + count, 0)) {
    throw new Error('Invalid typecheck baseline.');
  }
  return baseline.fingerprints;
}

function printSummary(current, baseline) {
  const folders = new Map();
  for (const [index, counts] of [current, baseline].entries()) {
    for (const [key, count] of Object.entries(counts)) {
      const file = key.split(' | ')[0];
      const parts = file.replace(/^resources\/js\//, '').split('/');
      const folder = file.startsWith('resources/js/')
        ? (parts[0] === 'Components' ? parts.slice(0, 2).join('/') : parts.length > 1 ? parts[0] : '(root)')
        : '(outside resources/js)';
      for (const name of folder.startsWith('Components/') ? ['Components', folder] : [folder]) {
        const totals = folders.get(name) ?? [0, 0];
        totals[index] += count;
        folders.set(name, totals);
      }
    }
  }
  console.log('Folder: current / baseline');
  for (const [folder, totals] of [...folders].sort(([a], [b]) => a.localeCompare(b))) {
    console.log(`  ${folder}: ${totals[0]} / ${totals[1]}`);
  }
}

export async function main(args = process.argv.slice(2)) {
  if (args.some((arg) => !['--update', '--allow-increase', '--strict-baseline'].includes(arg))
    || (args.includes('--allow-increase') && !args.includes('--update'))) {
    throw new Error('Usage: typecheck-ratchet.mjs [--update [--allow-increase]] [--strict-baseline]');
  }
  const result = await new Promise((resolve) => {
    exec(process.env.TYPECHECK_COMMAND ?? 'npx vue-tsc --noEmit --pretty false', {
      cwd: repoRoot, encoding: 'utf8', maxBuffer: 64 * 1024 * 1024,
      env: { ...process.env, npm_config_yes: 'false', npm_config_offline: 'true' },
    }, (error, stdout, stderr) => resolve({
      stdout, stderr, status: error ? error.code : 0, signal: error?.signal,
      error: error && typeof error.code !== 'number' ? error : undefined,
    }));
  });
  const output = `${result.stdout ?? ''}\n${result.stderr ?? ''}`;
  const { diagnostics, unparsed } = parse(output);
  if (result.error || result.signal || ![0, 1, 2].includes(result.status)
    || (result.status !== 0 && diagnostics.length === 0) || unparsed.length > 0
    || /^\s*(?:FATAL ERROR|Segmentation fault|Unhandled)/m.test(output)) {
    console.error(output);
    throw new Error(`vue-tsc failed or produced unrecognized output (exit ${result.status}): ${result.error?.message ?? result.signal ?? 'see output above'}`);
  }
  const locations = new Map();
  for (const diagnostic of diagnostics) {
    const key = fingerprint(diagnostic);
    const entries = locations.get(key) ?? [];
    entries.push(`${key.split(' | ')[0]}:${diagnostic.line}:${diagnostic.column}`);
    locations.set(key, entries);
  }
  const current = Object.fromEntries([...locations].map(([key, entries]) => [key, entries.length]).sort(([a], [b]) => a < b ? -1 : a > b ? 1 : 0));
  const baseline = readBaseline();
  const changes = compare(current, baseline);
  printSummary(current, baseline);
  for (const entry of changes.increased) {
    console.error(`New/increased (${entry.current} vs ${entry.baseline}): ${entry.fingerprint}\n  ${locations.get(entry.fingerprint).join('\n  ')}`);
  }
  if (changes.increased.length && !(args.includes('--update') && args.includes('--allow-increase'))) return 1;
  const total = diagnostics.length;
  if (args.includes('--update')) {
    writeFileSync(baselinePath, `${JSON.stringify({ version: 1, total, fingerprints: current }, null, 2)}\n`);
    console.log(`Baseline updated: ${total} errors.`);
    return 0;
  }
  const fixed = changes.decreased.reduce((sum, entry) => sum + entry.baseline - entry.current, 0);
  if (fixed) console.log(`${fixed} errors fixed — run \`npm run typecheck:baseline\` to lock in the improvement`);
  if (args.includes('--strict-baseline') && fixed) {
    console.error('Baseline is stale; lock in all decreased counts before merging.');
    return 1;
  }
  console.log(`Typecheck ratchet passed: ${total} existing errors, no increases.`);
  return 0;
}

if (process.argv[1] && import.meta.url === pathToFileURL(path.resolve(process.argv[1])).href) {
  try {
    // mocks/i18n.ts imports the gitignored catalogues; without them CI and local baselines diverge.
    const { generateI18nTranslationFiles } = await import('../vite-plugins/i18n-split.ts');
    generateI18nTranslationFiles(repoRoot);
    process.exitCode = await main();
  } catch (error) {
    console.error(error.message);
    process.exitCode = 1;
  }
}
