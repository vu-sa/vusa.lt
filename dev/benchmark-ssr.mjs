import { readFile } from 'node:fs/promises';
import { performance } from 'node:perf_hooks';

const [fixturePath, endpoint = 'http://127.0.0.1:13714', seconds = '300', rate = '5'] = process.argv.slice(2);
if (!fixturePath) throw new Error('Usage: node dev/benchmark-ssr.mjs fixtures.json [endpoint] [seconds] [requests/sec]');
const renderUrl = new URL(endpoint);
if (!['http:', 'https:'].includes(renderUrl.protocol)
  || !['127.0.0.1', '[::1]'].includes(renderUrl.hostname)
  || renderUrl.username || renderUrl.password) {
  throw new Error('SSR benchmark endpoint must use HTTP(S) and a literal loopback address (127.0.0.1 or [::1]) without credentials');
}
renderUrl.pathname = '/render';
renderUrl.search = '';
renderUrl.hash = '';
const pages = JSON.parse(await readFile(fixturePath, 'utf8'));
if (!Array.isArray(pages) || !pages.length || pages.some(page => page.props?.auth)) {
  throw new Error('Provide an array of anonymous Inertia page objects');
}
const timings = [];
let errors = 0;
async function render(page) {
  const start = performance.now();
  try {
    const response = await fetch(renderUrl, {
      method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(page),
      redirect: 'error',
      signal: AbortSignal.timeout(5000),
    });
    const result = await response.json();
    if (!response.ok || !result.body?.includes('data-server-rendered="true"')) throw new Error(result.error ?? 'Missing SSR body');
    timings.push(performance.now() - start);
  }
  catch (error) { errors++; console.error(error.message); }
}
for (const page of pages) {
  const start = timings.length;
  await render(page);
  const cold = timings.at(-1);
  for (let i = 0; i < 5; i++) await render(page);
  console.log(JSON.stringify({ component: page.component, locale: page.props.app?.locale, coldMs: cold, warmMs: timings.slice(start + 1) }));
}
const count = Math.ceil(Number(seconds) * Number(rate));
const jobs = [];
const started = performance.now();
for (let i = 0; i < count; i++) {
  const delay = started + i * 1000 / Number(rate) - performance.now();
  if (delay > 0) await new Promise(resolve => setTimeout(resolve, delay));
  jobs.push(render(pages[i % pages.length]));
  if (i && i % (Number(rate) * 60) === 0) console.log(`Completed ${i}/${count} scheduled requests`);
}
await Promise.all(jobs);
await Promise.all(Array.from({ length: 35 }, (_, i) => render(pages[i % pages.length])));
timings.sort((a, b) => a - b);
console.log(JSON.stringify({ requests: timings.length + errors, errors, p50Ms: timings[Math.floor(timings.length * .5)], p95Ms: timings[Math.floor(timings.length * .95)], maxMs: timings.at(-1) }, null, 2));
if (errors || timings[Math.floor(timings.length * .95)] >= 500) process.exitCode = 1;
