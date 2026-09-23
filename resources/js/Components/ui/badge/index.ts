import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Badge } from './Badge.vue';

export const badgeVariants = cva(
  'inline-flex items-center gap-1 border font-bold uppercase tracking-wide transition-colors focus:outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
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
        emerald: 'border-transparent bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300',
        amber: 'border-transparent bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300',
        sky: 'border-transparent bg-sky-100 text-sky-800 dark:bg-sky-900/30 dark:text-sky-300',
        zinc: 'border-transparent bg-secondary text-muted-foreground',
      },
      size: {
        default: 'px-2 py-0.5 text-[11px]',
        tiny: 'px-1.5 py-0 text-[11px]',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
    },
  },
);

export type BadgeVariants = VariantProps<typeof badgeVariants>;
