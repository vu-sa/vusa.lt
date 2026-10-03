import { trans as $t } from 'laravel-vue-i18n';

import type { HeadingAccent, HeadingSize, HeadingSpacing } from './CustomHeading';
import type { RCTagColor } from './RCTag';
import type { TextAlignValue } from './TextAlign';

/** Built lazily so labels follow the locale loaded at call time, not at import. */
export function headingSizeOptions(): { value: HeadingSize; label: string }[] {
  return [
    { value: 'sm', label: $t('rich-content.heading_size_sm') },
    { value: 'md', label: $t('rich-content.heading_size_md') },
    { value: 'lg', label: $t('rich-content.heading_size_lg') },
    { value: 'xl', label: $t('rich-content.heading_size_xl') },
  ];
}

export function headingAccentOptions(): { value: HeadingAccent; label: string; swatch?: string }[] {
  return [
    { value: 'none', label: $t('rich-content.heading_accent_none') },
    { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
    { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
    { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
  ];
}

export function headingSpacingOptions(): { value: HeadingSpacing; label: string }[] {
  return [
    { value: 'default', label: $t('rich-content.heading_spacing_default') },
    { value: 'tight', label: $t('rich-content.heading_spacing_tight') },
    { value: 'loose', label: $t('rich-content.heading_spacing_loose') },
    { value: 'none', label: $t('rich-content.heading_spacing_none') },
  ];
}

export function alignmentOptions(): { value: TextAlignValue; label: string }[] {
  return [
    { value: 'start', label: $t('rich-content.align_left') },
    { value: 'center', label: $t('rich-content.align_center') },
    { value: 'end', label: $t('rich-content.align_right') },
  ];
}

export function tagColorOptions(): { value: RCTagColor; label: string; swatch: string }[] {
  return [
    { value: 'zinc', label: $t('rich-content.colors.gray'), swatch: 'bg-zinc-500' },
    { value: 'red', label: $t('rich-content.colors.red'), swatch: 'bg-red-500' },
    { value: 'yellow', label: $t('rich-content.colors.yellow'), swatch: 'bg-yellow-500' },
    { value: 'green', label: $t('rich-content.colors.green'), swatch: 'bg-green-500' },
  ];
}

export function headingLevelLabel(level: 'paragraph' | 2 | 3 | 4): string {
  return level === 'paragraph' ? $t('rich-content.heading_paragraph') : $t(`rich-content.heading_level_${level}`);
}
