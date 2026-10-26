export function isValidTiptapLink(url: string): boolean {
  const value = url.trim();
  if (!value || [...value].some(character => character.charCodeAt(0) <= 32 || character.charCodeAt(0) === 127)) return false;
  if (/^\/(?!\/)/.test(value) || value.startsWith('#')) return true;

  try {
    return ['http:', 'https:', 'mailto:'].includes(new URL(value).protocol);
  }
  catch {
    return false;
  }
}
