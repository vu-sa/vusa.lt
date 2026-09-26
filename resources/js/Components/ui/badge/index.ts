import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
  'inline-flex items-center gap-1 border font-bold transition-colors focus:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-primary-foreground',
        secondary: 'border-border bg-secondary text-secondary-foreground',
        destructive: 'border-status-danger-border bg-status-danger-surface text-status-danger',
        outline: 'border-border text-foreground',
        success: 'border-status-success-border bg-status-success-surface text-status-success',
        warning: 'border-status-attention-border bg-status-attention-surface text-status-attention',
        rose: 'border-status-danger-border bg-status-danger-surface text-status-danger',
        // Content-authored colours (navigation link badges); not statuses.
        emerald: 'border-border bg-secondary text-foreground before:size-1.5 before:bg-cat-8 before:content-[\'\']',
        amber: 'border-border bg-secondary text-foreground before:size-1.5 before:bg-cat-7 before:content-[\'\']',
        sky: 'border-border bg-secondary text-foreground before:size-1.5 before:bg-cat-2 before:content-[\'\']',
        zinc: 'border-transparent bg-secondary text-muted-foreground',
      },
      size: {
        default: 'px-2 py-0.5 text-[11px]',
        tiny: 'px-1.5 py-0 text-[11px]',
      },
      voice: {
        brand: 'uppercase tracking-wide',
        sentence: 'normal-case tracking-normal',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
      voice: 'sentence',
    },
  },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
