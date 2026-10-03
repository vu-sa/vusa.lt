import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Card } from './Card.vue';
export { default as CardAction } from './CardAction.vue';
export { default as CardContent } from './CardContent.vue';
export { default as CardDescription } from './CardDescription.vue';
export { default as CardFooter } from './CardFooter.vue';
export { default as CardHeader } from './CardHeader.vue';
export { default as CardTitle } from './CardTitle.vue';

export const cardVariants = cva(
  'flex flex-col border transition-colors',
  {
    variants: {
      variant: {
        default: 'border-border bg-card text-card-foreground',
        surface: 'border-border bg-secondary/50 text-foreground',
        interactive: 'border-border bg-card text-card-foreground hover:border-brand/40 hover:bg-secondary/40',
        ghost: 'border-transparent bg-transparent',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

export const cardHeaderVariants = cva(
  'flex flex-col space-y-1.5',
  {
    variants: {
      size: {
        default: 'p-6',
        compact: 'p-4 pb-3',
        sm: 'p-3',
      },
    },
    defaultVariants: {
      size: 'default',
    },
  },
);

export const cardContentVariants = cva(
  'flex-grow pt-0',
  {
    variants: {
      size: {
        default: 'p-6 pt-0',
        compact: 'p-4 pt-0',
        sm: 'p-3 pt-0',
      },
    },
    defaultVariants: {
      size: 'default',
    },
  },
);

export type CardVariants = VariantProps<typeof cardVariants>;
export type CardHeaderVariants = VariantProps<typeof cardHeaderVariants>;
export type CardContentVariants = VariantProps<typeof cardContentVariants>;
