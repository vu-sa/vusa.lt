import type { NodeKind } from './types';

/** The VU SA structure palette: one fill per kind of body, so every graph reads the same way. */
export const nodeKindClasses: Record<NodeKind, { node: string; text: string }> = {
  assembly: { node: 'bg-brand-fixed text-brand-fixed-foreground', text: 'uppercase font-bold tracking-wide' },
  leader: { node: 'bg-foreground text-background', text: 'uppercase font-bold tracking-wide' },
  council: { node: 'bg-vusa-yellow text-ink', text: 'uppercase font-bold tracking-wide' },
  body: { node: 'bg-card text-card-foreground', text: 'uppercase font-bold tracking-wide' },
  group: { node: 'text-muted-foreground', text: 'uppercase font-bold tracking-wider' },
};
