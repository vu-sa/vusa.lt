/** One row look for actions, recents and hits: the highlighted row gets the brand rule ActionChoiceButton uses. */
export const paletteRowClass = [
  'u-touch group relative cursor-pointer gap-3 px-3 py-2 transition-colors',
  'before:absolute before:inset-y-1 before:left-0 before:w-0.5 before:bg-transparent before:transition-colors',
  'hover:bg-secondary/60 data-[highlighted]:bg-secondary data-[highlighted]:before:bg-brand-fill',
];
