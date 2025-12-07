import { cva, type VariantProps } from 'class-variance-authority'

export { default as Button } from './Button.vue'

/**
 * ShadcnVue Button - replaces NButton from Naive UI
 *
 * Migration from NButton:
 * | NButton prop              | ShadcnVue Button equivalent                    |
 * |---------------------------|------------------------------------------------|
 * | type="primary"            | variant="default"                              |
 * | type="error"              | variant="destructive"                          |
 * | type="warning"            | variant="warning"                              |
 * | type="success"            | variant="success"                              |
 * | ghost/tertiary/quaternary | variant="ghost"                                |
 * | secondary                 | variant="secondary"                            |
 * | text                      | variant="link" or variant="ghost"              |
 * | circle                    | size="icon" + class="rounded-full"             |
 * | round                     | class="rounded-full"                           |
 * | size="tiny"               | size="icon-xs" or size="xs"                    |
 * | size="small"              | size="sm" or size="icon-sm"                    |
 * | tag="a"                   | as="a"                                         |
 * | :loading                  | Use <Spinner /> + :disabled                    |
 * | #icon slot                | Place icon directly as child (auto-sized)      |
 *
 * Icon sizing: SVGs auto-size to size-4 unless explicit size class is set.
 * For loading: <Button :disabled="loading"><Spinner v-if="loading" />Text</Button>
 * For groups: Use <ButtonGroup> from @/Components/ui/button-group
 */
export const buttonVariants = cva(
  'inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm font-medium tracking-normal transition-all disabled:pointer-events-none disabled:opacity-50 [&_svg]:pointer-events-none [&_svg:not([class*=\'size-\'])]:size-4 shrink-0 [&_svg]:shrink-0 outline-none focus-visible:border-zinc-950 focus-visible:ring-zinc-950/50 focus-visible:ring-[3px] aria-invalid:ring-red-500/20 dark:aria-invalid:ring-red-500/40 aria-invalid:border-red-500 dark:focus-visible:border-zinc-300 dark:focus-visible:ring-zinc-300/50 dark:aria-invalid:ring-red-900/20 dark:dark:aria-invalid:ring-red-900/40 dark:aria-invalid:border-red-900',
  {
    variants: {
      variant: {
        default:
          'bg-zinc-900 text-zinc-50 shadow-xs hover:bg-zinc-900/90 dark:bg-zinc-50 dark:text-zinc-900 dark:hover:bg-zinc-50/90',
        destructive:
          'bg-red-500 text-white shadow-xs hover:bg-red-500/90 focus-visible:ring-red-500/20 dark:focus-visible:ring-red-500/40 dark:bg-red-500/60 dark:bg-red-900 dark:hover:bg-red-900/90 dark:focus-visible:ring-red-900/20 dark:dark:focus-visible:ring-red-900/40 dark:dark:bg-red-900/60',
        outline:
          'border bg-white shadow-xs hover:bg-zinc-100 hover:text-zinc-900 dark:bg-zinc-200/30 dark:border-zinc-200 dark:hover:bg-zinc-200/50 dark:bg-zinc-950 dark:hover:bg-zinc-800 dark:hover:text-zinc-50 dark:dark:bg-zinc-800/30 dark:dark:border-zinc-800 dark:dark:hover:bg-zinc-800/50',
        secondary:
          'bg-zinc-100 text-zinc-900 shadow-xs hover:bg-zinc-100/80 dark:bg-zinc-800 dark:text-zinc-50 dark:hover:bg-zinc-800/80',
        ghost:
          'hover:bg-zinc-100 hover:text-zinc-900 dark:hover:bg-zinc-100/50 dark:hover:bg-zinc-800 dark:hover:text-zinc-50 dark:dark:hover:bg-zinc-800/50',
        link: 'text-zinc-900 underline-offset-4 hover:underline dark:text-zinc-50',
        success:
          'bg-green-600 text-white shadow-xs hover:bg-green-600/90 focus-visible:ring-green-500/20 dark:bg-green-700 dark:hover:bg-green-700/90',
        warning:
          'bg-amber-500 text-white shadow-xs hover:bg-amber-500/90 focus-visible:ring-amber-500/20 dark:bg-amber-600 dark:hover:bg-amber-600/90',
      },
      size: {
        xs: 'h-7 rounded-md gap-1 px-2 has-[>svg]:px-1.5 text-[12px] leading-tight tracking-normal',
        default: 'h-9 px-4 py-2 has-[>svg]:px-3',
        sm: 'h-8 rounded-md gap-1.5 px-3 has-[>svg]:px-2.5 text-[13px] leading-tight tracking-normal',
        lg: 'h-10 rounded-md px-6 has-[>svg]:px-4 text-base tracking-normal',
        icon: 'size-9',
        'icon-sm': 'size-8',
        'icon-xs': 'size-6',
      },
      animation: {
        none: '',
        subtle: 'hover:scale-105',
        bounce: 'hover:scale-110 active:scale-95',
      },
    },
    defaultVariants: {
      variant: 'default',
      size: 'default',
      animation: 'none',
    },
  },
)

export type ButtonVariants = VariantProps<typeof buttonVariants>
