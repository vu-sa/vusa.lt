import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export const tagChipVariants = cva(
  'inline-flex items-center px-2.5 py-1 text-[0.6875rem] font-bold uppercase tracking-[0.14em]',
  {
    variants: {
      variant: {
        solid: 'bg-brand-fill text-brand-foreground',
        outline: 'border border-brand text-brand hover:bg-brand/10',
        muted: 'border border-border text-muted-foreground hover:border-brand hover:text-brand',
      },
    },
    defaultVariants: {
      variant: 'solid',
    },
  },
);

export type TagChipVariants = VariantProps<typeof tagChipVariants>;
