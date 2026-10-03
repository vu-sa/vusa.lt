import { describe, expect, it } from 'vitest';

import { buttonVariants } from '..';

describe('buttonVariants', () => {
  it('defaults brand actions to uppercase and other actions to sentence case', () => {
    expect(buttonVariants({ variant: 'brand' }).split(' ')).toEqual(expect.arrayContaining(['uppercase', 'tracking-wide']));

    for (const variant of ['default', 'outline', 'ghost', 'secondary', 'destructive'] as const) {
      const classes = buttonVariants({ variant }).split(' ');
      expect(classes).toContain('normal-case');
      expect(classes).not.toContain('uppercase');
    }
  });

  it('honors an explicit voice on any variant', () => {
    expect(buttonVariants({ variant: 'outline', voice: 'brand' }).split(' ')).toContain('uppercase');
    expect(buttonVariants({ variant: 'brand', voice: 'sentence' }).split(' ')).toContain('normal-case');
    expect(buttonVariants({ variant: 'ghost', voice: 'plain' }).split(' ')).toContain('font-medium');
    expect(buttonVariants({ variant: 'link', voice: 'brand' }).split(' ')).toContain('uppercase');
    expect(buttonVariants({ variant: 'link' }).split(' ')).toContain('font-medium');
  });

  it('gives the sentence voice its own 40px action height at the default size', () => {
    const classes = buttonVariants({ voice: 'sentence' }).split(' ');

    expect(classes).toEqual(expect.arrayContaining(['min-h-10', 'h-auto', 'text-sm', 'normal-case', 'pointer-coarse:min-h-11']));
  });

  it('leaves an explicit size alone', () => {
    expect(buttonVariants({ voice: 'sentence', size: 'sm' })).not.toContain('min-h-10');
    expect(buttonVariants({ variant: 'brand' })).not.toContain('min-h-10');
  });
});
