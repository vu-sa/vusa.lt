<template>
  <span class="min-w-0">
    <template v-if="!variants"><SearchMatch v-if="displayMatch" inline :match="displayMatch" /><template v-else>{{ displayName }}</template></template>
    <template v-else>
      <template v-if="stemLead"><SearchMatch v-if="stemLeadMatch" inline :match="stemLeadMatch" /><template v-else>{{ stemLead }}</template></template>
      <!-- Keep the final stem word and ending together when the name wraps. -->
      <span data-testid="duty-ending-group" class="inline-flex items-baseline whitespace-nowrap">
        <span><SearchMatch v-if="stemTailMatch" inline :match="stemTailMatch" /><template v-else>{{ stemTail }}</template></span>
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger as-child>
              <span data-testid="duty-ending-trigger" class="relative inline-grid shrink-0">
                <span
                  aria-hidden="true"
                  data-testid="duty-ending-masculine"
                  :class="[endingClasses, showFeminine ? 'opacity-0 -translate-y-[0.12em] select-none' : 'translate-y-0 opacity-100']"
                ><SearchMatch v-if="masculineEndingMatch" inline :match="masculineEndingMatch" /><template v-else>{{ variants.masculineEnding }}</template></span>
                <span
                  aria-hidden="true"
                  data-testid="duty-ending-feminine"
                  :class="[endingClasses, showFeminine ? 'translate-y-0 opacity-100' : 'opacity-0 translate-y-[0.12em] select-none']"
                ><SearchMatch v-if="feminineEndingMatch" inline :match="feminineEndingMatch" /><template v-else>{{ variants.feminineEnding }}</template></span>
                <!-- Keep the rule in the grid so clamped headings paint it. -->
                <span
                  data-testid="duty-ending-underline"
                  class="pointer-events-none col-start-1 row-start-1 h-px self-end bg-brand"
                />
              </span>
            </TooltipTrigger>
            <TooltipContent side="top" class="max-w-xs">
              {{ $t('forms.helpers.duty_name_inflected_tooltip') }}
            </TooltipContent>
          </Tooltip>
        </TooltipProvider>
      </span>
      <template v-if="variants.suffix"><SearchMatch v-if="suffixMatch" inline :match="suffixMatch" /><template v-else>{{ variants.suffix }}</template></template>
      <span data-testid="duty-gender-pair" class="sr-only select-none">{{ genderPairLabel }}</span>
    </template>
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import { changeDutyNameEndings, getDutyNameGenderVariants, type DutyNameHolder } from '@/Utils/String';
import { useDutyGenderFlip } from '@/Composables/useDutyGenderFlip';
import SearchMatch from '@/Components/ui/SearchMatch.vue';
import { matchTitle, sliceMatch, type SearchMatch as SearchMatchData } from '@/Shared/Search/matches';

/** A position alternates endings; a holder’s assignment keeps their inflected name. */
const props = withDefaults(defineProps<{
  name: string;
  /** Defaults to the page's locale; anything but 'lt' always renders plain text. */
  locale?: string;
  holder?: DutyNameHolder | null;
  useOriginalDutyName?: boolean;
  matches?: SearchMatchData[];
}>(), {
  locale: undefined,
  holder: null,
});

const endingClasses = 'col-start-1 row-start-1 transition-[opacity,translate] duration-700 ease-in-out motion-reduce:transition-none';

const effectiveLocale = computed(() => props.locale ?? usePage().props.app.locale);

const variants = computed(() => (
  !props.holder && !props.useOriginalDutyName && effectiveLocale.value === 'lt'
    ? getDutyNameGenderVariants(props.name)
    : null
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

const displayName = computed(() => props.holder
  ? changeDutyNameEndings(props.holder, props.name, effectiveLocale.value, props.holder.pronouns, props.useOriginalDutyName)
  : props.name);

const displayMatch = computed(() => matchTitle(displayName.value, props.matches));
const stemMatch = computed(() => {
  if (displayMatch.value || !variants.value) return displayMatch.value;
  const { stem, feminineEnding, masculineEnding, suffix } = variants.value;
  return matchTitle(stem + feminineEnding + suffix, props.matches)
    ?? matchTitle(stem + masculineEnding + suffix, props.matches);
});
const stemLeadMatch = computed(() => sliceMatch(stemMatch.value, 0, stemLead.value.length));
const stemTailMatch = computed(() => sliceMatch(stemMatch.value, stemLead.value.length, variants.value?.stem.length ?? 0));
const suffixMatch = computed(() => sliceMatch(displayMatch.value, props.name.length - (variants.value?.suffix.length ?? 0), props.name.length));

function endingMatch(ending: string): SearchMatchData | undefined {
  if (!variants.value) return undefined;
  const { stem, suffix } = variants.value;
  const exact = matchTitle(stem + ending + suffix, props.matches);
  if (exact) return sliceMatch(exact, stem.length, stem.length + ending.length);
  const original = sliceMatch(displayMatch.value, stem.length, props.name.length - suffix.length);
  if (!original) return undefined;
  return { field: original.field, segments: [{ text: ending, matched: original.segments.some(segment => segment.matched) }] };
}

const masculineEndingMatch = computed(() => endingMatch(variants.value?.masculineEnding ?? ''));
const feminineEndingMatch = computed(() => endingMatch(variants.value?.feminineEnding ?? ''));

const { showFeminine } = useDutyGenderFlip(computed(() => variants.value !== null));

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
