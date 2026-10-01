import { mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { createRequire } from 'node:module';
import { createHash } from 'node:crypto';

import type { Plugin } from 'vite';
import { getIconData } from '@iconify/utils/lib/icon-set/get-icon';
import type { IconifyIcon, IconifyJSON } from '@iconify/types';

export function normalizeFluentIcons(collection: IconifyJSON): Record<string, IconifyIcon> {
  const icons: Record<string, IconifyIcon> = {};

  for (const name of [...Object.keys(collection.icons), ...Object.keys(collection.aliases ?? {})]) {
    const icon = getIconData(collection, name);
    if (icon) icons[name] = icon;
  }

  return icons;
}

export default function fluentIcons(): Plugin {
  let root: string;
  let source: string | undefined;

  const catalogueSource = () => {
    if (!source) {
      const require = createRequire(resolve(root, 'package.json'));
      const collection = JSON.parse(readFileSync(require.resolve('@iconify-json/fluent/icons.json'), 'utf8')) as IconifyJSON;
      source = JSON.stringify(normalizeFluentIcons(collection));
    }
    return source;
  };

  const version = () => createHash('sha256').update(catalogueSource()).digest('hex');

  // Only PHP reads the catalogue, so it stays out of public/build; deploys ship this directory.
  const write = () => {
    const path = resolve(root, 'bootstrap/icons/fluent-icons.json');
    mkdirSync(dirname(path), { recursive: true });
    writeFileSync(path, catalogueSource());
    writeFileSync(`${path}.version`, version());
  };

  return {
    name: 'public-fluent-icons',
    configResolved(config) {
      root = config.root;
    },
    configureServer() {
      write();
    },
    writeBundle() {
      write();
    },
  };
}
