import { latinizeId } from '@/Utils/String';

export function richText(node: unknown): string {
  if (!node || typeof node !== 'object') return '';
  const value = node as { text?: string; content?: unknown[] };
  return value.text ?? value.content?.map(richText).join('') ?? '';
}

const VALID_ID = /^[A-Za-z0-9][A-Za-z0-9_-]*$/;

export function collectHeadingIds(value: unknown, ids = new Set<string>()): Set<string> {
  if (!value || typeof value !== 'object') return ids;
  const record = value as Record<string, unknown>;
  const id = (record.attrs as Record<string, unknown> | undefined)?.id;
  if (record.type === 'heading' && typeof id === 'string' && VALID_ID.test(id)) ids.add(id);
  Object.values(record).forEach(child => collectHeadingIds(child, ids));
  return ids;
}

/** With `locked`, only those ids survive a retitle; a heading typed this session follows its text. */
export function normalizeHeadingAnchors(value: unknown, locked?: Set<string>): void {
  const used = new Set<string>();
  function walk(node: unknown) {
    if (!node || typeof node !== 'object') return;
    if (Array.isArray(node)) { node.forEach(walk); return; }
    const record = node as Record<string, unknown>;
    if (record.type === 'heading') {
      const attrs = (record.attrs ??= {}) as Record<string, unknown>;
      const old = typeof attrs.id === 'string' && VALID_ID.test(attrs.id) && (!locked || locked.has(attrs.id)) ? attrs.id : '';
      const base = old || latinizeId(richText(record)) || 'heading';
      let id = base;
      let suffix = 1;
      while (used.has(id)) id = `${base}-${suffix++}`;
      attrs.id = id;
      used.add(id);
    }
    Object.values(record).forEach(walk);
  }
  walk(value);
}
