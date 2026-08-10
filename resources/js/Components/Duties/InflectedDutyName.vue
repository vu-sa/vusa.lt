<template>
  <span class="min-w-0">
    <template v-if="!variants">{{ name }}</template>
    <template v-else>
      <template v-if="stemLead">{{ stemLead }}</template>
      <!-- The last stem word rides along with the ending so a wrapping name never
           breaks between "Koordinator" and its animated "-ius/-ė". -->
      <span data-testid="duty-ending-group" class="inline-flex items-baseline whitespace-nowrap">
        <span>{{ stemTail }}</span>
        <!-- Only the ending is the tooltip's trigger, so the tooltip and its arrow point at
             the letters that actually change instead of at the middle of the whole name. -->
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <span data-testid="duty-ending-trigger" class="relative inline-grid shrink-0">
                <span
                  aria-hidden="true"
                  data-testid="duty-ending-masculine"
                  :class="[endingClasses, showFeminine ? 'opacity-0 -translate-y-[0.12em] select-none bg-left' : 'translate-y-0 opacity-100 bg-right']"
                >{{ variants.masculineEnding }}</span>
                <span
                  aria-hidden="true"
                  data-testid="duty-ending-feminine"
                  :class="[endingClasses, showFeminine ? 'translate-y-0 opacity-100 bg-right' : 'opacity-0 translate-y-[0.12em] select-none bg-left']"
                >{{ variants.feminineEnding }}</span>
                <span
                  data-testid="duty-ending-underline"
                  class="pointer-events-none absolute inset-x-0 -bottom-[0.08em] h-px bg-gradient-to-r from-transparent via-muted-foreground/60 to-transparent"
                />
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              {{ $t('forms.helpers.duty_name_inflected_tooltip') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </span>
      <!-- Everything after the head noun ("… VU FF Taryboje", "… SPK"), free to wrap. -->
      <template v-if="variants.suffix">{{ variants.suffix }}</template>
      <span data-testid="duty-gender-pair" class="sr-only select-none">{{ genderPairLabel }}</span>
    </template>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { getDutyNameGenderVariants } from '@/Utils/String';
import { useDutyGenderFlip } from '@/Composables/useDutyGenderFlip';

/**
 * Shows a Lithuanian duty name as fixed text with the gendered ending of its head noun
 * slowly rolling between the masculine and feminine form — the head noun is often mid-name
 * ("Studentų <b>atstovas</b> VU FF Taryboje"), so anything after it renders unchanged after
 * the animated letters. A passive signal, for anywhere a
 * duty is shown without a holder attached, that the stored name is not tied to one gender
 * (holders get it inflected automatically per pronoun or name, see `changeDutyNameEndings`).
 * This exists because admins reading a frozen "Koordinatorius" here have no way to know it
 * will read "Koordinatorė" for a feminine holder, and create a duplicate duty instead.
 *
 * Renders plain, static text when the name has no detectable gendered ending (e.g. "Grupė",
 * "Taryba") or when `locale` isn't Lithuanian — English duty titles aren't gendered.
 */
const props = withDefaults(defineProps<{
  name: string;
  /** Defaults to the page's locale; anything but 'lt' always renders plain text. */
  locale?: string;
}>(), {
  locale: undefined,
});

/**
 * The ending's colour is a gradient that resolves to the inherited text colour once
 * settled, and drifts through the brand red while the form changes — so the swap reads as
 * a tint sweeping through the letters rather than a hard recolour.
 *
 * `-webkit-text-fill-color` rather than `text-transparent` on purpose: setting `color`
 * would make `via-current`/`to-current` resolve to transparent, losing whatever colour the
 * surrounding heading, table cell or `group-hover:` state gives the rest of the name.
 *
 * The transition lists `translate`, not `transform` — Tailwind v4's `translate-*` utilities
 * set the standalone `translate` property, which `transition-[…transform…]` never animates.
 */
const endingClasses = 'col-start-1 row-start-1 bg-gradient-to-r from-vusa-red via-current to-current dark:from-vusa-red-on-dark bg-[length:200%_100%] bg-clip-text [-webkit-background-clip:text] [-webkit-text-fill-color:transparent] transition-[opacity,translate,background-position] duration-700 ease-in-out';

const effectiveLocale = computed(() => props.locale ?? usePage().props.app.locale);

const variants = computed(() => (
  effectiveLocale.value === 'lt' ? getDutyNameGenderVariants(props.name) : null
));

/** Everything up to the last space — free to wrap; empty for single-word names. */
const stemLead = computed(() => {
  const stem = variants.value?.stem ?? '';
  const lastSpace = stem.lastIndexOf(' ');
  return lastSpace === -1 ? '' : stem.slice(0, lastSpace + 1);
});

/** The final stem word, kept on one line with the ending. */
const stemTail = computed(() => {
  const stem = variants.value?.stem ?? '';
  return stem.slice(stemLead.value.length);
});

const { showFeminine } = useDutyGenderFlip();

const genderPairLabel = computed(() => {
  if (!variants.value) {
    return '';
  }
  const { stem, masculineEnding, feminineEnding, suffix } = variants.value;
  return $t('forms.helpers.duty_name_gender_pair', {
    masculine: stem + masculineEnding + suffix,
    feminine: stem + feminineEnding + suffix,
  });
});
</script>
