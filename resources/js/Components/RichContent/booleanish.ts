/**
 * Some boolean content fields arrive as the strings "1"/"0" (or "true"/"false"):
 * editor forms submitted as FormData (see EditHomePage.vue's `forceFormData`)
 * stringify every scalar that way, and Inertia encodes booleans as "1"/"0".
 * `ContentPart::booted` normalizes new writes, but rows saved before that — and
 * the unsaved preview endpoint's responses — can still be stringly, and "0" is
 * truthy, so plain truthiness checks read them wrong.
 */
export function asBoolean(value: unknown): boolean {
  if (typeof value === 'string') {
    return value === '1' || value === 'true';
  }

  return value === true || value === 1;
}
