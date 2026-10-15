import type { VariantProps } from 'class-variance-authority';
import { cva } from 'class-variance-authority';

/**
 * The bordered uppercase control both surfaces use for filter chips, the Filtrai / Rikiuoti
 * triggers and popover triggers. `active` means "this narrows what you see", not "hovered".
 */
export const controlVariants = cva(
  [
    'inline-flex shrink-0 items-center justify-center gap-2 border',
    'text-xs font-bold transition-colors',
    'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40',
    'disabled:pointer-events-none disabled:opacity-50 [&_svg]:shrink-0',
  ],
  {
    variants: {
      size: {
        sm: 'h-8 px-3 pointer-coarse:min-h-11',
        default: 'h-11 px-3 sm:px-4',
      },
      voice: {
        brand: 'uppercase tracking-wide',
        sentence: 'normal-case tracking-normal',
      },
      active: {
        true: 'border-brand bg-brand/5 text-brand hover:bg-brand/10',
        false: 'border-border bg-background text-foreground hover:border-brand hover:text-brand',
      },
    },
    defaultVariants: {
      size: 'default',
      voice: 'brand',
      active: false,
    },
  },
);

/** The small count square that rides inside an active control ("Filtrai 3"). */
export const controlCountClass = 'flex h-4 min-w-4 items-center justify-center bg-brand-fill px-1 font-mono text-[0.625rem] leading-none text-brand-foreground';

/** A segmented toggle (view modes): one hairline frame, the chosen segment is brand-filled. */
export const segmentGroupClass = 'inline-flex h-11 shrink-0 border border-border bg-background p-0.5 pointer-coarse:h-12';

export const segmentVariants = cva(
  [
    'flex h-full items-center justify-center gap-1.5 px-3',
    'text-xs font-bold uppercase tracking-wide transition-colors',
    'outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40 [&_svg]:size-4 [&_svg]:shrink-0',
  ],
  {
    variants: {
      active: {
        true: 'bg-brand-fill text-brand-foreground',
        false: 'text-muted-foreground hover:text-foreground',
      },
    },
    defaultVariants: {
      active: false,
    },
  },
);

/** The search field that opens a filter band: hairline box, brand border on focus. */
export const searchFieldClass = [
  'h-11 w-full border border-border bg-background pl-10 pr-9 text-sm text-foreground',
  'placeholder:text-muted-foreground/70 transition-colors focus:border-brand focus:outline-none',
].join(' ');

/** A text field, select or picker trigger on a form's tinted canvas; add `h-11` for single-line controls. */
export const fieldSurfaceClass = [
  'border-border bg-secondary/50 transition-colors',
  'focus:bg-background focus:border-brand focus:ring-2 focus:ring-brand/20',
].join(' ');

export type ControlVariants = VariantProps<typeof controlVariants>;
export type SegmentVariants = VariantProps<typeof segmentVariants>;
