export function safeHref(value: unknown): string | undefined {
  if (typeof value !== 'string') return undefined;

  const href = value.trim();
  const normalized = href.replace(/[\u0000-\u0020\u007f]/g, '');
  const scheme = /^([a-z][a-z0-9+.-]*):/i.exec(normalized)?.[1]?.toLowerCase();

  if (scheme && !['http', 'https', 'mailto', 'tel'].includes(scheme)) return undefined;

  return href || undefined;
}
