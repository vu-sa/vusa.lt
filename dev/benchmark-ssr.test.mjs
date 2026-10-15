import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { once } from 'node:events';
import { mkdtemp, rm, writeFile } from 'node:fs/promises';
import { createServer } from 'node:http';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';

const script = new URL('./benchmark-ssr.mjs', import.meta.url);
const page = { component: 'Public/HomePage', props: { app: { locale: 'lt' } } };

async function runBenchmark(fixture, endpoint) {
  const child = spawn(process.execPath, [script.pathname, fixture, endpoint, '0', '5']);
  let stdout = '';
  let stderr = '';
  child.stdout.on('data', chunk => { stdout += chunk; });
  child.stderr.on('data', chunk => { stderr += chunk; });
  const [code] = await once(child, 'close');
  return { code, stdout, stderr };
}

async function fixtureFor(t) {
  const directory = await mkdtemp(join(tmpdir(), 'ssr-benchmark-'));
  t.after(() => rm(directory, { recursive: true, force: true }));
  const fixture = join(directory, 'pages.json');
  await writeFile(fixture, JSON.stringify([page]));
  return fixture;
}

async function listen(t, handler) {
  const server = createServer(handler);
  server.listen(0, '127.0.0.1');
  await once(server, 'listening');
  t.after(() => new Promise(resolve => server.close(resolve)));
  return `http://127.0.0.1:${server.address().port}`;
}

test('rejects external endpoints before reading fixtures', async () => {
  for (const endpoint of ['https://example.com', 'http://localhost', 'http://127.0.0.1.example.com', 'http://127.0.0.1@example.com', 'http://user:password@127.0.0.1', 'ftp://127.0.0.1']) {
    const result = await runBenchmark('/missing-fixture.json', endpoint);
    assert.equal(result.code, 1);
    assert.match(result.stderr, /literal loopback address/);
    assert.doesNotMatch(result.stderr, /ENOENT/);
  }
});

test('sends fixtures to the local renderer', async t => {
  const fixture = await fixtureFor(t);
  let requests = 0;
  const endpoint = await listen(t, async (request, response) => {
    assert.equal(request.method, 'POST');
    assert.equal(request.url, '/render');
    let body = '';
    for await (const chunk of request) body += chunk;
    assert.deepEqual(JSON.parse(body), page);
    requests++;
    response.setHeader('Content-Type', 'application/json');
    response.end(JSON.stringify({ body: '<div data-server-rendered="true"></div>' }));
  });
  const result = await runBenchmark(fixture, endpoint);
  assert.equal(result.code, 0, result.stderr);
  assert.equal(requests, 41);
  assert.match(result.stdout, /"errors": 0/);
});

test('never forwards fixtures through redirects', async t => {
  const fixture = await fixtureFor(t);
  let forwarded = 0;
  const target = await listen(t, (request, response) => {
    forwarded++;
    response.end('{}');
  });
  const endpoint = await listen(t, (request, response) => {
    response.writeHead(307, { Location: `${target}/render` });
    response.end();
  });
  const result = await runBenchmark(fixture, endpoint);
  assert.equal(result.code, 1);
  assert.equal(forwarded, 0);
  assert.match(result.stdout, /"errors": 41/);
});
