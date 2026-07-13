/**
 * Shared styling recipe for interactive (clickable/navigable) admin cards, tiles and list rows.
 *
 * Cards are flat (no shadow) at rest; on hover they gain a subtle primary-tinted
 * gradient plus a primary border, signalling clickability consistently across the
 * admin UI. Pair this with a `rounded-*`, `border` and `bg-card` base on the element.
 */
export const interactiveCardClass
  = 'transition-colors hover:border-primary/30 hover:bg-gradient-to-br hover:from-primary/5 hover:to-primary/10';
