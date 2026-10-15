import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Textarea } from './Textarea.vue';

export const textareaVariants = cva(
  [
    'flex field-sizing-content min-h-16 w-full border transition-colors outline-none',
    'placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground',
    'disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
    'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
  ],
  {
    variants: {
      variant: {
        default: [
          'border-border bg-transparent px-3 py-2 text-sm dark:bg-input/30',
          'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
        ],
        surface: [
          'border-border bg-secondary/50 px-3.5 py-2.5 text-sm',
          'focus-visible:bg-background focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20',
        ],
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  },
);

export type TextareaVariants = VariantProps<typeof textareaVariants>;
