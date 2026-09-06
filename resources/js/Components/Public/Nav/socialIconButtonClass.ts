/**
 * The square, hairline-bordered treatment for an icon-only social/utility control — the
 * footer's Facebook/Instagram/START FM row, matching the header's own `navButtonClass`
 * idiom (MainNavigation.vue). Kept as one shared string so the two chrome locations can't
 * drift apart, and so the public.md "explicit dark: on every ui/ override" trap only has
 * to be paid off once.
 */
export const socialIconButtonClass = 'size-10 border border-border text-foreground/70 transition-colors duration-200 '
  + 'hover:border-brand hover:bg-transparent hover:text-brand '
  + 'dark:hover:bg-transparent dark:hover:text-brand';
