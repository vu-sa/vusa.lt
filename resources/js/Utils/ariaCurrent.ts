/**
 * `aria-current` for an Inertia `<Link>`. Bound as an object because `vuejs-accessibility` cannot
 * see that `Link` renders an `<a>` and rejects the attribute written out statically.
 */
export const ariaCurrent = (active: boolean, value: 'page' | 'true' = 'page'): { 'aria-current'?: string } =>
  (active ? { 'aria-current': value } : {});
