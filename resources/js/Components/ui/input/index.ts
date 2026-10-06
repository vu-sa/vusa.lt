import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Input } from './Input.vue';
export { default as InputWithOverlappingLabel } from './InputWithOverlappingLabel.vue';

export const inputVariants = cva(
  [
    'w-full min-w-0 border transition-colors outline-none',
    'file:inline-flex file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground',
    'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
    'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
  ],
  {
    variants: {
      variant: {
        default: [
          'border-border bg-transparent dark:bg-input/30',
          'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
        ],
        surface: [
          'border-border bg-secondary/50',
          'focus-visible:bg-background focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20',
        ],
        ghost: 'border-transparent bg-transparent focus-visible:border-border',
      },
      size: {
        default: 'h-11 px-3.5 py-2 text-sm',
        sm: 'h-9 px-3 py-1 text-xs md:text-sm',
        xs: 'h-8 px-2.5 py-1 text-xs',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

export type InputVariants = VariantProps<typeof inputVariants>;
