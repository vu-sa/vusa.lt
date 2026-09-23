import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Button } from './Button.vue';

/**
 * One button for both surfaces. Colour comes only from tokens, so the same variant is VU SA red
 * on the light canvas and amber on near-black, admin and public alike.
 *
 * `voice` carries the type: bold uppercase is the default across the design system.
 * `sentence` is for home quick actions; `plain` is for controls such as calendar day cells.
 */
export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap transition-colors disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40 aria-invalid:border-destructive aria-invalid:ring-destructive/20',
  {
    variants: {
      variant: {
        'default': 'bg-primary text-primary-foreground hover:bg-primary/90',
        'brand': 'bg-brand-fill text-brand-foreground hover:bg-brand-fill/90 focus-visible:ring-brand/30',
        'outline': 'border border-border bg-transparent text-foreground hover:border-brand hover:text-brand aria-expanded:border-brand aria-expanded:text-brand',
        'secondary': 'bg-secondary text-secondary-foreground hover:bg-secondary/80',
        'ghost': 'text-foreground hover:bg-accent hover:text-accent-foreground aria-expanded:bg-accent',
        'link': 'text-brand underline-offset-4 hover:underline',
        'destructive': 'bg-destructive text-white hover:bg-destructive/90 focus-visible:ring-destructive/30',
        'success': 'border border-status-success-border bg-status-success-surface text-status-success hover:border-status-success',
        'warning': 'border border-status-attention-border bg-status-attention-surface text-status-attention hover:border-status-attention',
        // Fixed-dark grounds (hero scrim, ink panels), where light-mode `--foreground` is near-black.
        'brand-outline-on-dark': 'border border-white/25 bg-transparent text-white hover:border-brand hover:text-brand',
      },
      voice: {
        brand: 'font-bold uppercase tracking-wide',
        sentence: 'font-bold normal-case tracking-normal',
        plain: 'font-medium normal-case',
      },
      size: {
        'default': 'h-11 px-4 text-xs',
        'xs': 'h-8 gap-1.5 px-2.5 text-xs',
        'sm': 'h-9 gap-1.5 px-3 text-xs pointer-coarse:min-h-11',
        'lg': 'h-12 px-5 text-sm',
        'icon': 'size-9 pointer-coarse:size-11',
        'icon-xs': 'size-6',
        'icon-sm': 'size-8 pointer-coarse:size-11',
        'icon-lg': 'size-11',
      },
    },
    compoundVariants: [
      { variant: 'link', class: 'h-auto px-0 font-medium normal-case tracking-normal' },
    ],
    defaultVariants: {
      variant: 'default',
      voice: 'brand',
      size: 'default',
    },
  },
);
export type ButtonVariants = VariantProps<typeof buttonVariants>;
