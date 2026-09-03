<template>
  <Popover>
    <PopoverTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        :class="cn('border border-transparent', !isDefault && 'border-brand text-brand', props.class)"
        :aria-label="$t('accessibility.menu_open')"
        :title="$t('accessibility.menu_title')"
        data-slot="accessibility-menu-trigger"
      >
        <!-- `size-4`, matching the other utility icons in the header (search, dark mode) — this
             one previously ran a step larger than its neighbours at `size-5`. -->
        <IFluentAccessibility24Regular class="size-4" />
        <!-- A colour change alone reaches neither a screen reader nor anyone who cannot pick the
             brand hue out from the default foreground. -->
        <span v-if="!isDefault" class="sr-only">{{ $t('accessibility.preferences_active') }}</span>
      </Button>
    </PopoverTrigger>

    <PopoverContent align="end" class="w-80 p-0" data-slot="accessibility-menu">
      <div class="flex items-center justify-between gap-4 border-b border-border px-4 py-3">
        <p class="u-eyebrow flex items-center gap-2">
          <IFluentAccessibility24Regular class="size-4" aria-hidden="true" />
          {{ $t('accessibility.menu_title') }}
        </p>
        <button
          v-if="!isDefault"
          type="button"
          class="flex items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
          @click="reset"
        >
          <IFluentArrowReset24Regular class="size-3.5" aria-hidden="true" />
          {{ $t('accessibility.reset') }}
        </button>
      </div>

      <div class="border-b border-border px-4 py-4">
        <p id="a11y-text-size-label" class="mb-3 flex items-center gap-2 text-sm font-semibold text-foreground">
          <IFluentTextFontSize24Regular class="size-4 text-brand" aria-hidden="true" />
          {{ $t('accessibility.text_size') }}
        </p>

        <!-- A stepper rather than four labelled buttons: the sizes are an ordered scale, so
             "bigger / smaller" is the operation a reader actually wants, and the filled segments
             show where they are on it without needing to read four labels. -->
        <div
          class="flex items-stretch border border-border"
          role="group"
          aria-labelledby="a11y-text-size-label"
        >
          <button
            type="button"
            class="flex w-11 shrink-0 items-center justify-center border-r border-border text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground disabled:opacity-40 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
            :disabled="scaleIndex === 0"
            :aria-label="$t('accessibility.text_size_decrease')"
            @click="step(-1)"
          >
            <IFluentSubtract24Regular class="size-4" />
          </button>

          <div class="flex flex-1 items-center justify-between gap-3 px-3 py-2.5">
            <span class="flex flex-1 items-center gap-1.5" aria-hidden="true">
              <span
                v-for="(option, index) in fontScaleOptions"
                :key="option.value"
                :class="[
                  'h-1.5 flex-1',
                  index <= scaleIndex ? 'bg-brand-fill' : 'bg-secondary',
                ]"
              />
            </span>
            <span class="w-7 shrink-0 text-right text-sm font-bold text-foreground">
              {{ currentOption.label }}
            </span>
          </div>

          <button
            type="button"
            class="flex w-11 shrink-0 items-center justify-center border-l border-border text-muted-foreground transition-colors hover:bg-secondary hover:text-foreground disabled:opacity-40 disabled:hover:bg-transparent focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
            :disabled="scaleIndex === fontScaleOptions.length - 1"
            :aria-label="$t('accessibility.text_size_increase')"
            @click="step(1)"
          >
            <IFluentAdd24Regular class="size-4" />
          </button>
        </div>

        <!-- The segments are decorative; this is what a screen reader announces on change. -->
        <p class="sr-only" aria-live="polite">
          {{ $t('accessibility.text_size_option', { size: currentOption.label }) }}
        </p>
      </div>

      <CheckControl v-model="contrast" :label="$t('accessibility.high_contrast')" class="border-b border-border" />
      <CheckControl v-model="underlineLinks" :label="$t('accessibility.underline_links')" />
    </PopoverContent>
  </Popover>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentAccessibility24Regular from '~icons/fluent/accessibility-24-regular';
import IFluentAdd24Regular from '~icons/fluent/add-24-regular';
import IFluentArrowReset24Regular from '~icons/fluent/arrow-reset-24-regular';
import IFluentSubtract24Regular from '~icons/fluent/subtract-24-regular';
import IFluentTextFontSize24Regular from '~icons/fluent/text-font-size-24-regular';

import CheckControl from './CheckControl.vue';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { cn } from '@/Utils/Shadcn/utils';
import { type FontScaleKey, useAccessibilityPreferences } from '@/Composables/useAccessibilityPreferences';

/**
 * Reader preferences, replacing the third-party UserWay widget: text size, high contrast, and
 * forced link underlines, persisted per browser.
 *
 * The controls only set state — the CSS that reacts to it lives in `resources/css/app.css` and is
 * scoped to the public surface. See `useAccessibilityPreferences`.
 */
const props = withDefaults(defineProps<{
  class?: HTMLAttributes['class'];
}>(), {
  class: undefined,
});

const { fontScale, contrast, underlineLinks, isDefault, reset } = useAccessibilityPreferences();

const fontScaleOptions: { value: FontScaleKey; label: string }[] = [
  { value: 's', label: 'S' },
  { value: 'm', label: 'M' },
  { value: 'l', label: 'L' },
  { value: 'xl', label: 'XL' },
];

const scaleIndex = computed(() => fontScaleOptions.findIndex(option => option.value === fontScale.value));

const currentOption = computed(() => fontScaleOptions[scaleIndex.value] ?? fontScaleOptions[1]);

function step(direction: 1 | -1): void {
  const next = fontScaleOptions[scaleIndex.value + direction];

  if (next) {
    fontScale.value = next.value;
  }
}
</script>
