// @vitest-environment node

import { exec } from 'node:child_process';
import { readFileSync, writeFileSync } from 'node:fs';

import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

import { compare, fingerprint, main, parse } from '../typecheck-ratchet.mjs';

vi.mock('node:child_process', () => ({ exec: vi.fn() }));
vi.mock('node:fs', () => ({ readFileSync: vi.fn(), writeFileSync: vi.fn() }));

describe('typecheck ratchet', () => {
  it('parses diagnostics and keeps indented continuation lines with their message', () => {
    const result = parse('resources/js/A.vue(12,3): error TS2322: Type is incompatible.\r\n  Details here.\r\n    More details.\r\nC:\\repo\\B.ts(4,5): error TS2345: Invalid argument.\r\n');
    expect(result).toEqual({
      diagnostics: [
        { file: 'resources/js/A.vue', line: 12, column: 3, code: 'TS2322', message: 'Type is incompatible.\n  Details here.\n    More details.' },
        { file: 'C:\\repo\\B.ts', line: 4, column: 5, code: 'TS2345', message: 'Invalid argument.' },
      ],
      unparsed: [],
    });
  });

  it('reports unparseable errors, global diagnostics and crashes', () => {
    expect(parse('error TS18003: No inputs found.\nfile.ts: error TS1234: bad\nError: crashed').unparsed)
      .toEqual(['error TS18003: No inputs found.', 'file.ts: error TS1234: bad', 'Error: crashed']);
    expect(parse('').diagnostics).toEqual([]);
    expect(parse('npm notice run npx\n').unparsed).toEqual([]);
    expect(parse('A.ts(1,2): error TS2322: Invalid.\n  B.ts: error TS1234: malformed').unparsed)
      .toEqual(['  B.ts: error TS1234: malformed']);
  });

  it('normalizes paths, repository prefixes and first-line whitespace without locations', () => {
    const diagnostic = { file: '/repo/resources/js/./A.vue', code: 'TS2322', line: 1, column: 2, message: '  Cannot   use /repo/resources/js/B.ts.\n  Different details.' };
    expect(fingerprint(diagnostic, '/repo')).toBe('resources/js/A.vue | TS2322 | Cannot use resources/js/B.ts.');
    expect(fingerprint({ ...diagnostic, file: 'C:\\repo\\resources\\js\\A.vue', message: 'Cannot\t use C:\\repo\\resources\\js\\B.ts.', line: 50 }, 'C:\\repo'))
      .toBe(fingerprint(diagnostic, '/repo'));
  });

  it('distinguishes new, increased, decreased and stale fingerprints', () => {
    expect(compare({ added: 1, repeated: 3, fixed: 1, unchanged: 2 }, { repeated: 2, fixed: 2, gone: 1, unchanged: 2 }))
      .toEqual({
        increased: [
          { fingerprint: 'added', current: 1, baseline: 0 },
          { fingerprint: 'repeated', current: 3, baseline: 2 },
        ],
        decreased: [
          { fingerprint: 'fixed', current: 1, baseline: 2 },
          { fingerprint: 'gone', current: 0, baseline: 1 },
        ],
        stale: [{ fingerprint: 'gone', current: 0, baseline: 1 }],
      });
    expect(compare({}, {})).toEqual({ increased: [], decreased: [], stale: [] });
  });
});

describe('ratchet modes', () => {
  const output = 'resources/js/A.ts(1,2): error TS2322: Invalid.\nresources/js/A.ts(8,9): error TS2322: Invalid.';
  const key = 'resources/js/A.ts | TS2322 | Invalid.';

  function baseline(count) {
    vi.mocked(readFileSync).mockReturnValue(JSON.stringify({ version: 1, total: count, fingerprints: count ? { [key]: count } : {} }));
  }

  function compiler(stdout = output, code = 2, stderr = '') {
    vi.mocked(exec).mockImplementation((_command, _options, callback) => {
      callback(code ? Object.assign(new Error('compiler exit'), { code }) : null, stdout, stderr);
    });
  }

  beforeEach(() => {
    vi.clearAllMocks();
    vi.spyOn(console, 'log').mockImplementation(() => {});
    vi.spyOn(console, 'error').mockImplementation(() => {});
    baseline(2);
    compiler();
  });

  afterEach(() => vi.restoreAllMocks());

  it('passes unchanged counts in strict mode', async () => {
    expect(await main(['--strict-baseline'])).toBe(0);
  });

  it('rejects increased counts with current locations, including in update mode', async () => {
    baseline(1);
    expect(await main([])).toBe(1);
    expect(console.error).toHaveBeenCalledWith(expect.stringContaining('resources/js/A.ts:8:9'));
    expect(await main(['--update'])).toBe(1);
    expect(writeFileSync).not.toHaveBeenCalled();
  });

  it('permits an explicit baseline increase and writes sorted JSON with counts', async () => {
    baseline(0);
    expect(await main(['--update', '--allow-increase'])).toBe(0);
    expect(writeFileSync).toHaveBeenCalledWith(expect.any(URL), `${JSON.stringify({ version: 1, total: 2, fingerprints: { [key]: 2 } }, null, 2)}\n`);
  });

  it('passes decreases locally but requires locking them in for CI', async () => {
    baseline(3);
    expect(await main([])).toBe(0);
    expect(console.log).toHaveBeenCalledWith('1 errors fixed — run `npm run typecheck:baseline` to lock in the improvement');
    expect(await main(['--strict-baseline'])).toBe(1);
    expect(await main(['--update'])).toBe(0);
  });

  it('locks in a completely clean compiler run', async () => {
    compiler('', 0);
    expect(await main(['--strict-baseline'])).toBe(1);
    expect(await main(['--update'])).toBe(0);
    expect(writeFileSync).toHaveBeenCalledWith(expect.any(URL), `${JSON.stringify({ version: 1, total: 0, fingerprints: {} }, null, 2)}\n`);
  });

  it.each([
    ['', 1, ''],
    ['', 127, 'vue-tsc: not found'],
    [output, 134, ''],
    [output, 2, 'error TS18003: No inputs found.'],
    [output, 2, 'FATAL ERROR: Out of memory'],
  ])('rejects missing, crashed or unparseable compiler output', async (stdout, code, stderr) => {
    compiler(stdout, code, stderr);
    await expect(main(['--update', '--allow-increase'])).rejects.toThrow('vue-tsc failed');
    expect(writeFileSync).not.toHaveBeenCalled();
  });
});
