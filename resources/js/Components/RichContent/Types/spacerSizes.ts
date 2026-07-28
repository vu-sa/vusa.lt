import type { Spacer } from '@/Types/contentParts';

export type SpacerSize = NonNullable<Spacer['options']['size']>;

export interface SpacerSizeOption {
  value: SpacerSize;
  /** i18n key suffix — paired with `rich-content.spacer_size_<suffix>`. */
  labelKey: string;
  /** Tailwind height utility applied to the rendered gap. */
  class: string;
  /** Approximate rendered height in rem, shown as a hint in the editor. */
  rem: number;
}

/**
 * Size → tailwind height class. Single source of truth shared by `SpacerDisplay`
 * (public render) and `SpacerEditor` (visual ruler preview), so the gap an author
 * picks in the editor is bit-for-bit the gap that ships on the public page.
 */
export const SPACER_SIZE_CLASS: Record<SpacerSize, string> = {
  'xs': 'h-2',
  'sm': 'h-4',
  'md': 'h-8',
  'lg': 'h-16',
  'xl': 'h-24',
  '2xl': 'h-32',
};

export const SPACER_SIZES: SpacerSizeOption[] = [
  { value: 'xs', labelKey: 'spacer_size_xs', class: SPACER_SIZE_CLASS.xs!, rem: 0.5 },
  { value: 'sm', labelKey: 'spacer_size_sm', class: SPACER_SIZE_CLASS.sm!, rem: 1 },
  { value: 'md', labelKey: 'spacer_size_md', class: SPACER_SIZE_CLASS.md!, rem: 2 },
  { value: 'lg', labelKey: 'spacer_size_lg', class: SPACER_SIZE_CLASS.lg!, rem: 4 },
  { value: 'xl', labelKey: 'spacer_size_xl', class: SPACER_SIZE_CLASS.xl!, rem: 6 },
  { value: '2xl', labelKey: 'spacer_size_2xl', class: SPACER_SIZE_CLASS['2xl']!, rem: 8 },
];

export const DEFAULT_SPACER_SIZE: SpacerSize = 'md';
