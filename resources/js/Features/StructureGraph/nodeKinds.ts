import type { NodeKind } from './types';

/** The VU SA structure palette: one fill per kind of body, so every graph reads the same way. */
export const nodeKindClasses: Record<NodeKind, { node: string; text: string }> = {
  assembly: { node: 'bg-brand-fixed text-brand-fixed-foreground', text: 'uppercase font-extrabold' },
  leader: { node: 'bg-foreground text-background', text: 'uppercase font-extrabold' },
  council: { node: 'bg-vusa-yellow text-white', text: 'uppercase font-extrabold' },
  body: { node: 'bg-secondary text-secondary-foreground', text: 'uppercase font-extrabold' },
  group: { node: 'bg-transparent text-muted-foreground', text: 'uppercase font-bold tracking-wide' },
};
