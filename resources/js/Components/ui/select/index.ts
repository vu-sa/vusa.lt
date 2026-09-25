import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Select } from './Select.vue';
export { default as SelectContent } from './SelectContent.vue';
export { default as SelectGroup } from './SelectGroup.vue';
export { default as SelectItem } from './SelectItem.vue';
export { default as SelectItemText } from './SelectItemText.vue';
export { default as SelectLabel } from './SelectLabel.vue';
export { default as SelectScrollDownButton } from './SelectScrollDownButton.vue';
export { default as SelectScrollUpButton } from './SelectScrollUpButton.vue';
export { default as SelectSeparator } from './SelectSeparator.vue';
export { default as SelectTrigger } from './SelectTrigger.vue';
export { default as SelectValue } from './SelectValue.vue';
export { default as SelectVirtualizer } from './SelectVirtualizer.vue';

export const selectTriggerVariants = cva(
  [
    'flex w-fit items-center justify-between gap-2 border text-sm whitespace-nowrap transition-colors outline-none',
    'placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50',
    'aria-invalid:border-destructive aria-invalid:ring-destructive/20',
    '*:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 *:data-[slot=select-value]:line-clamp-1',
    '[&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*=\'size-\'])]:size-4 [&_svg:not([class*=\'text-\'])]:text-muted-foreground',
  ],
  {
    variants: {
      variant: {
        default: [
          'border-border bg-transparent text-foreground dark:bg-input/30',
          'focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
        ],
        surface: [
          'border-border bg-secondary/50 text-foreground',
          'focus-visible:bg-background focus-visible:border-brand focus-visible:ring-2 focus-visible:ring-brand/20',
        ],
      },
      size: {
        default: 'h-11 px-3.5 py-2',
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

export type SelectTriggerVariants = VariantProps<typeof selectTriggerVariants>;
