<template>
  <component
    :is="isExpandable ? 'details' : 'div'"
    class="group/item"
    :name="disclosureGroup"
  >
    <component
      :is="isExpandable ? 'summary' : 'div'"
      class="grid grid-cols-[2rem_1fr] items-start gap-x-3 gap-y-1 px-1 py-3 sm:grid-cols-[2rem_1fr_auto] sm:px-2"
      :class="isExpandable
        ? 'cursor-pointer list-none transition-colors hover:bg-secondary/60'
        : ''"
    >
      <!-- Number then status dot, in that order, so hiding the dot never shifts the numbers -->
      <span class="flex items-center gap-1.5 pt-0.5">
        <span class="w-4 text-right font-mono text-xs tabular-nums text-muted-foreground">{{ item.order }}</span>
        <span
          class="size-1.5 shrink-0 rounded-full"
          :class="showStatus ? status.dotClass : 'bg-transparent'"
          :title="showStatus ? status.label : undefined"
        />
      </span>

      <div class="min-w-0">
        <p class="text-sm font-medium leading-snug text-foreground">
          {{ item.title }}
        </p>
        <p
          v-if="showStatus || item.brought_by_students || isExpandable"
          class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-xs text-muted-foreground"
        >
          <span v-if="showStatus" :class="status.colorClass">{{ status.label }}</span>
          <span v-if="showStatus && item.brought_by_students" aria-hidden="true">·</span>
          <span v-if="item.brought_by_students" class="inline-flex items-center gap-1">
            <IFluentPeople20Regular class="size-3" />
            {{ $t('Įtraukta studentų') }}
          </span>
          <template v-if="isExpandable">
            <span v-if="showStatus || item.brought_by_students" aria-hidden="true">·</span>
            <span class="inline-flex items-center gap-0.5 text-muted-foreground group-open/item:hidden">
              {{ $t('Plačiau') }}
              <IFluentChevronDown20Regular class="size-3" />
            </span>
            <span class="hidden items-center gap-0.5 text-muted-foreground group-open/item:inline-flex">
              {{ $t('Suskleisti') }}
              <IFluentChevronUp20Regular class="size-3" />
            </span>
          </template>
        </p>
      </div>

      <!-- Slot and outcome sit together on the right; on mobile they wrap under the title -->
      <div class="col-start-2 flex flex-wrap items-center gap-1.5 sm:col-start-3 sm:justify-end">
        <span
          v-if="timeRangeLabel"
          class="font-mono text-xs tabular-nums text-muted-foreground"
        >{{ timeRangeLabel }}</span>

        <template v-if="showsOutcome">
          <span
            class="inline-flex items-center gap-1 border border-border px-2 py-0.5 font-mono text-xs font-medium"
            :class="[status.bgClass, decisionColorClass]"
          >
            {{ getDecisionLabel(mainVote?.decision) }}
          </span>
          <span
            v-if="requiresStudentPerspective && canCompareVotes(item)"
            class="inline-flex items-center gap-1 text-xs"
            :class="getVoteComparisonColorClass(item)"
            :title="getVoteComparisonText(item)"
          >
            <component :is="isVoteAligned(item) ? IFluentCheckmarkCircle20Regular : IFluentErrorCircle20Regular" class="size-3.5" />
            <span class="hidden sm:inline">{{ $t('Studentai') }}</span>
          </span>
        </template>
      </div>
    </component>

    <!-- Expanded detail: the long text and the full vote breakdown -->
    <div v-if="isExpandable" class="my-2 space-y-3 border-l-2 border-border/80 bg-secondary/30 px-3 py-3 pl-[2.75rem] sm:pl-[3rem]">
      <p
        v-if="item.description"
        class="text-sm leading-relaxed text-foreground/90"
      >
        {{ item.description }}
      </p>

      <dl v-if="showsOutcome" class="flex flex-wrap gap-x-6 gap-y-1.5 text-xs">
        <div v-if="requiresStudentPerspective" class="flex items-center gap-1.5">
          <dt class="text-muted-foreground">
            {{ $t('Studentų balsas') }}
          </dt>
          <dd :class="getVoteTextColorClass(mainVote?.student_vote)">
            {{ getStudentVoteLabel(mainVote?.student_vote) || '—' }}
          </dd>
        </div>
        <div class="flex items-center gap-1.5">
          <dt class="text-muted-foreground">
            {{ $t('Sprendimas') }}
          </dt>
          <dd :class="decisionColorClass">
            {{ getDecisionLabel(mainVote?.decision) }}
          </dd>
        </div>
        <div v-if="requiresStudentPerspective" class="flex items-center gap-1.5">
          <dt class="text-muted-foreground">
            {{ $t('Nauda studentams') }}
          </dt>
          <dd :class="getVoteTextColorClass(mainVote?.student_benefit)">
            {{ getStudentBenefitLabel(mainVote?.student_benefit) }}
          </dd>
        </div>
      </dl>
    </div>
  </component>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import {
  canCompareVotes,
  getAgendaItemStatusMeta,
  getDecisionLabel,
  getDecisionTextColorClass,
  getMainVote,
  getStudentBenefitLabel,
  getStudentVoteLabel,
  getVoteComparisonColorClass,
  getVoteComparisonText,
  getVoteTextColorClass,
  hasDecisionData,
  isVoteAligned,
} from '@/Composables/useAgendaItemStyling';
import IFluentCheckmarkCircle20Regular from '~icons/fluent/checkmark-circle-20-regular';
import IFluentChevronDown20Regular from '~icons/fluent/chevron-down-20-regular';
import IFluentChevronUp20Regular from '~icons/fluent/chevron-up-20-regular';
import IFluentErrorCircle20Regular from '~icons/fluent/error-circle-20-regular';
import IFluentPeople20Regular from '~icons/fluent/people-20-regular';

const props = withDefaults(defineProps<{
  item: App.Entities.AgendaItem;
  /** False for VU SA's own bodies — only the outcome is shown. */
  requiresStudentPerspective?: boolean;
  /** The meeting has not happened yet, so "not discussed" states say nothing. */
  isUpcoming?: boolean;
  /**
   * Shared `name` so opening one row closes the others — keeps a long agenda scannable.
   * Native to <details name>; browsers without support just allow several open at once.
   */
  disclosureGroup?: string;
}>(), {
  requiresStudentPerspective: true,
  isUpcoming: false,
  disclosureGroup: undefined,
});

const status = computed(() => getAgendaItemStatusMeta(props.item, props.requiresStudentPerspective));
const mainVote = computed(() => getMainVote(props.item));

/**
 * Some statuses are prompts to the editor, not information for a reader:
 *  - `unset` ("Nepažymėtas") only means nobody has classified the question yet;
 *  - `no_vote` ("Neaptartas") is simply true of every item on a meeting still to come.
 * Showing either publicly makes an ordinary agenda look neglected.
 */
const showStatus = computed(() => {
  if (status.value.status === 'unset') return false;

  return !(props.isUpcoming && status.value.status === 'no_vote');
});

const showsOutcome = computed(() => props.item.type === 'voting' && hasDecisionData(props.item));
const decisionColorClass = computed(() => getDecisionTextColorClass(mainVote.value?.decision));

/** Only rows with something more to say are worth making clickable. */
const isExpandable = computed(() => Boolean(props.item.description) || showsOutcome.value);

/** The columns are TIME, which MySQL hands back as `HH:MM:SS`. */
const trimSeconds = (value: string) => value.slice(0, 5);

const timeRangeLabel = computed(() => {
  const { start_time: start, end_time: end } = props.item;
  if (!start) return null;

  return end ? `${trimSeconds(start)}–${trimSeconds(end)}` : trimSeconds(start);
});
</script>
