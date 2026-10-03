import { describe, expect, it } from 'vitest';

import { cardVariants } from '../index';

describe('cardVariants', () => {
  it('defaults to border-border bg-card and text-card-foreground', () => {
    const classes = cardVariants({}).split(' ');

    expect(classes).toContain('border-border');
    expect(classes).toContain('bg-card');
    expect(classes).toContain('text-card-foreground');
  });

  it('supports surface variant for form aside panels', () => {
    const classes = cardVariants({ variant: 'surface' }).split(' ');

    expect(classes).toContain('bg-secondary/50');
    expect(classes).toContain('border-border');
  });

  it('supports interactive variant for clickable cards', () => {
    const classes = cardVariants({ variant: 'interactive' }).split(' ');

    expect(classes).toContain('border-border');
    expect(classes).toContain('hover:border-brand/40');
    expect(classes).toContain('hover:bg-secondary/40');
  });
});
