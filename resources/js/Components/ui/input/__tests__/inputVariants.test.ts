import { describe, expect, it } from 'vitest';

import { inputVariants } from '../index';

describe('inputVariants', () => {
  it('defaults to h-11 default size and default transparent border variant', () => {
    const classes = inputVariants({}).split(' ');

    expect(classes).toContain('h-11');
    // Same hairline as outline buttons, so a field and its adjacent button read as one row.
    expect(classes).toContain('border-border');
    expect(classes).not.toContain('border-input');
    expect(classes).toContain('w-full');
  });

  it('supports the surface variant for form canvases', () => {
    const classes = inputVariants({ variant: 'surface' }).split(' ');

    expect(classes).toContain('bg-secondary/50');
    expect(classes).toContain('border-border');
    expect(classes).toContain('h-11');
  });

  it('supports sm size for compact controls', () => {
    const classes = inputVariants({ size: 'sm' }).split(' ');

    expect(classes).toContain('h-9');
  });

  it('supports xs size for tight toolbars', () => {
    const classes = inputVariants({ size: 'xs' }).split(' ');

    expect(classes).toContain('h-8');
  });
});
