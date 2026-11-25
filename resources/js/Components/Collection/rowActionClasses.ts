export const rowActionIconClass = [
  'flex size-9 items-center justify-center rounded-md border border-(--button-outline-border) bg-(--button-outline-fill) text-muted-foreground transition-colors active:bg-(--button-outline-active-fill)',
  'pointer-coarse:size-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');

export const rowActionLabelledClass = [
  'inline-flex min-h-9 items-center gap-1.5 rounded-md border pr-3 pl-2.5 text-xs font-semibold normal-case tracking-normal transition-colors',
  'pointer-coarse:min-h-11 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring disabled:pointer-events-none disabled:opacity-50',
].join(' ');

export const rowActionHoverClass = 'hover:border-(--button-outline-hover-border) hover:bg-(--button-outline-hover-fill) hover:text-(--button-outline-hover-text)';
