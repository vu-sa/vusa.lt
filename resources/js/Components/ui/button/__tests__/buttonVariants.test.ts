import { describe, expect, it } from 'vitest';

import { buttonVariants } from '..';

describe('buttonVariants', () => {
  it('gives the sentence voice its own 40px action height at the default size', () => {
    const classes = buttonVariants({ voice: 'sentence' }).split(' ');

    expect(classes).toEqual(expect.arrayContaining(['min-h-10', 'h-auto', 'text-sm', 'normal-case', 'pointer-coarse:min-h-11']));
  });

  it('leaves an explicit size alone', () => {
    expect(buttonVariants({ voice: 'sentence', size: 'sm' })).not.toContain('min-h-10');
    expect(buttonVariants({})).not.toContain('min-h-10');
  });
});
