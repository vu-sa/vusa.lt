import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Checkbox } from './Checkbox.vue';

export const checkboxVariants = cva([
  'peer flex shrink-0 items-center justify-center border border-input bg-background text-brand-foreground transition-colors outline-none',
  'focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-not-allowed disabled:opacity-50 aria-invalid:border-destructive',
  'data-[state=checked]:border-brand-fill data-[state=checked]:bg-brand-fill',
  'data-[state=indeterminate]:border-brand-fill data-[state=indeterminate]:bg-brand-fill',
], {
  variants: {
    size: {
      default: 'size-4 [&_svg]:size-3.5',
      md: 'size-4.5 [&_svg]:size-3.5',
      lg: 'size-7 border-2 [&_svg]:size-5',
    },
  },
  defaultVariants: { size: 'default' },
});

export type CheckboxVariants = VariantProps<typeof checkboxVariants>;
