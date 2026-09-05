import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

export { default as Button } from './Button.vue';

export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-zinc-950 focus-visible:ring-zinc-950/50 focus-visible:ring-[3px] aria-invalid:ring-red-500/20 aria-invalid:border-red-500 dark:focus-visible:border-zinc-300 dark:focus-visible:ring-zinc-300/50 dark:aria-invalid:ring-red-900/40 dark:aria-invalid:border-red-900',
  {
    variants: {
      variant: {
        default:
          'bg-zinc-900 text-zinc-50 hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90',
        destructive:
          'bg-red-500 text-white hover:bg-red-500/90 focus-visible:ring-red-500/20 dark:bg-red-900/60 dark:hover:bg-red-900/90 dark:focus-visible:ring-red-900/40',
        outline:
          'border bg-white shadow-xs hover:bg-zinc-100 hover:text-zinc-900 dark:bg-zinc-800/30 dark:border-zinc-800 dark:hover:bg-zinc-800/50 dark:hover:text-zinc-50',
        secondary:
          'bg-zinc-100 text-zinc-900 hover:bg-zinc-100/80 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-800/80',
        ghost:
          'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-800/50 dark:hover:text-zinc-50',
        link: 'text-zinc-900 underline-offset-4 hover:underline dark:text-zinc-50',
        success:
          'bg-green-600 text-white shadow-xs hover:bg-green-600/90 focus-visible:ring-green-500/20 dark:bg-green-700 dark:hover:bg-green-700/90',
        warning:
          'bg-amber-500 text-white shadow-xs hover:bg-amber-500/90 focus-visible:ring-amber-500/20 dark:bg-amber-600 dark:hover:bg-amber-600/90',
        // The public site's primary call to action. Token-driven rather than a fixed colour, so
        // it is VU SA red on the light canvas and amber on near-black — see `--brand-fill` in
        // app.css. Added as a variant rather than a new component because public code already
        // imports this Button in ~38 places.
        brand:
          'bg-brand-fill text-brand-foreground hover:bg-brand-fill/90 focus-visible:ring-brand/30 '
          + 'dark:bg-brand-fill dark:text-brand-foreground dark:hover:bg-brand-fill/90',
        // The hairline secondary that sits beside a `brand` primary all over the public site —
        // "Sinchronizuoti kalendorių" next to "Visi renginiai", and so on. Distinct from
        // `outline`, which hardcodes `bg-white`/zinc and belongs to admin.
        'brand-outline':
          'border border-border bg-transparent text-foreground hover:border-brand hover:bg-transparent hover:text-brand '
          + 'dark:bg-transparent dark:hover:bg-transparent dark:hover:text-brand',
      },
      size: {
        'default': 'h-9 px-4 py-2 has-[>svg]:px-3',
        'xs': 'h-7 rounded-md gap-1 px-2.5 has-[>svg]:px-2 text-xs',
        'sm': 'h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5',
        'lg': 'h-10 rounded-md px-6 has-[>svg]:px-4',
        'icon': 'size-9',
        'icon-sm': 'size-8',
        'icon-xs': 'size-6',
        'icon-lg': 'size-10',
        /*
         * Public-surface controls. Unusually for a `size`, these also set the type: on the public
         * site a button *is* bold uppercase with wide tracking, and splitting that across every
         * caller is exactly how the padding, casing and icon gaps drifted apart in the first
         * place. One prop, one look.
         *
         * Note the base already applies `gap-2` — icons inside a Button must NOT carry their own
         * `mr-2`, or the gap doubles.
         */
        'public': 'h-auto px-6 py-3.5 text-sm font-bold uppercase tracking-wide',
        'public-sm': 'h-auto px-3 py-2 text-xs font-bold uppercase tracking-wide',
      },
      animation: {
        none: '',
        subtle: 'hover:scale-[1.03] active:scale-95',
        bounce: 'animate-bounce',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
      animation: 'none',
    },
  },
);
export type ButtonVariants = VariantProps<typeof buttonVariants>;
