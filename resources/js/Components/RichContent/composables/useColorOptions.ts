import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

export interface ColorOption {
  label: string;
  value: string;
  /** Tailwind classes for preview swatch */
  swatch: string;
}

/**
 * Composable for shared color options used across RichContent editors.
 * Provides consistent color palette with translated labels.
 */
export function useColorOptions() {
  const colorOptions = computed<ColorOption[]>(() => [
    {
      label: $t('rich-content.colors.gray'),
      value: 'zinc',
      swatch: 'bg-zinc-500',
    },
    {
      label: $t('rich-content.colors.red'),
      value: 'red',
      swatch: 'bg-red-500',
    },
    {
      label: $t('rich-content.colors.yellow'),
      value: 'yellow',
      swatch: 'bg-yellow-500',
    },
  ]);

  const buttonColorOptions = computed<ColorOption[]>(() => [
    {
      label: $t('rich-content.colors.red'),
      value: 'red',
      swatch: 'bg-red-500',
    },
    {
      label: $t('rich-content.colors.yellow'),
      value: 'yellow',
      swatch: 'bg-yellow-500',
    },
    {
      label: $t('rich-content.colors.black'),
      value: 'zinc',
      swatch: 'bg-zinc-900',
    },
    {
      label: $t('rich-content.colors.white'),
      value: 'white',
      swatch: 'bg-white border border-zinc-200',
    },
  ]);

  return {
    colorOptions,
    buttonColorOptions,
  };
}
