import { mkdirSync, mkdtempSync, rmSync, writeFileSync } from 'fs';
import { tmpdir } from 'os';
import { join } from 'path';

import { afterEach, describe, expect, it, vi } from 'vitest';

import { generateI18nTranslationFiles } from '../i18n-split';

let root: string;

function langWith(file: string, content: string): string {
  root = mkdtempSync(join(tmpdir(), 'i18n-split-'));
  mkdirSync(join(root, 'lang', 'admin', 'lt'), { recursive: true });
  writeFileSync(join(root, 'lang', 'admin', 'lt', file), content);
  return root;
}

afterEach(() => rmSync(root, { recursive: true, force: true }));

describe('generateI18nTranslationFiles', () => {
  it('fails a strict run on a translation file that does not parse, rather than shipping without its keys', () => {
    langWith('broken.php', "<?php return ['a' => 'b'");

    expect(() => generateI18nTranslationFiles(root, { languages: ['lt'], strict: true })).toThrow(/broken\.php/);
  });

  it('only warns outside a strict run, so the dev server keeps serving', () => {
    vi.spyOn(console, 'warn').mockImplementation(() => {});
    vi.spyOn(console, 'log').mockImplementation(() => {});
    langWith('broken.php', "<?php return ['a' => 'b'");

    expect(() => generateI18nTranslationFiles(root, { languages: ['lt'] })).not.toThrow();
  });
});
