<template>
  <RCSection
    :id="anchorId ? `rc-${anchorId}` : undefined" :title="element.options?.title" :subtitle="element.options?.subtitle"
    :eyebrow="element.options?.eyebrow" :band
    :align="element.options?.align ?? 'center'" :heading-level="element.options?.headingLevel"
    :show-separator="element.options?.showSeparator" inner="wide"
    :editable @update:header="updateOptions"
  >
    <div ref="statisticsRef" class="relative flex flex-wrap mx-auto font-bold text-xl leading-tight justify-center gap-6 md:gap-8">
      <div v-for="(numberStat, index) in element.json_content" :key="index"
        class="group/stat relative cursor-pointer" :data-rc-interactive="editable ? '' : undefined"
        role="button" tabindex="0"
        @click="openStatisticOptions(index)"
        @keydown.enter.prevent="openStatisticOptions(index)"
        @keydown.space.prevent="openStatisticOptions(index)"
      >
        <NumberStatistic :end-number="numberStat.endNumber" :show-plus="numberStat.showPlus">
          <RCInlineText v-if="editable"
            as="span" :model-value="numberStat.label" :editable
            :placeholder="$t('rich-content.enter_stat_label')"
            @click.stop
            @update:model-value="updateStatistic(index, { ...numberStat, label: $event })"
          />
          <template v-else>
            {{ numberStat.label }}
          </template>
        </NumberStatistic>
        <button v-if="editable" type="button"
          :class="[
            'absolute -right-2 -top-2 flex size-6 items-center justify-center rounded-md text-muted-foreground',
            'opacity-0 transition-opacity group-hover/stat:opacity-100',
            'hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400',
          ]"
          data-rc-interactive data-rc-number-stat-remove
          :aria-label="$t('rich-content.remove_stat')" :title="$t('rich-content.remove_stat')"
          @click.stop="removeStatistic(index)"
        >
          <IFluentDelete24Regular class="size-3.5" />
        </button>
      </div>

      <RCAddPlaceholder
        v-if="editable"
        :label="$t('rich-content.add_stat')"
        data-rc-number-stat-add
        class="left-1/2 -bottom-2 -translate-x-1/2 translate-y-full"
        @click="addStatistic"
      />
    </div>

    <Popover v-if="editable" :open="hotspots?.isPopoverOpen(statisticHotspotId)" @update:open="onStatisticPopoverOpenChange">
      <PopoverAnchor :reference="statisticsRef ?? undefined" />
      <PopoverContent v-if="hotspots?.isPopoverOpen(statisticHotspotId) && currentStatistic" data-surface="public" class="w-64" @close-auto-focus.prevent>
        <div class="flex flex-col gap-4">
          <Field>
            <FieldLabel>{{ $t('rich-content.stat_number') }}</FieldLabel>
            <NumberField :model-value="currentStatistic.endNumber" @update:model-value="updateStatistic(currentStatisticIndex, { ...currentStatistic, endNumber: $event })" />
          </Field>
          <Field>
            <div class="flex items-center justify-between">
              <FieldLabel class="mb-0">
                {{ $t('rich-content.stat_show_plus') }}
              </FieldLabel>
              <Switch :model-value="currentStatistic.showPlus" @update:model-value="updateStatistic(currentStatisticIndex, { ...currentStatistic, showPlus: $event })" />
            </div>
          </Field>
        </div>
      </PopoverContent>
    </Popover>
  </RCSection>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, inject, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import RCSection from '../RCSection.vue';
import { ACTIVE_HOTSPOT_KEY } from '../Editor/Fullscreen/useActiveHotspot';
import type { BandResolution } from '../bandLayout';

import NumberStatistic from './RCNumberStatistic.vue';

import type { NumberStatSection } from '@/Types/contentParts';
import IFluentDelete24Regular from '~icons/fluent/delete24-regular';

// Lazy-loaded: only ever mounted while `editable` — a static import would bundle the
// full-screen editor's inline-text/add-placeholder controls and the stat-options
// popover (field/number-field/switch) into every public page that renders this block.
const RCInlineText = defineAsyncComponent(() => import('../Editor/Fullscreen/RCInlineText.vue'));
const RCAddPlaceholder = defineAsyncComponent(() => import('../Editor/Fullscreen/RCAddPlaceholder.vue'));
const Field = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.Field));
const FieldLabel = defineAsyncComponent(() => import('@/Components/ui/field').then(m => m.FieldLabel));
const NumberField = defineAsyncComponent(() => import('@/Components/ui/number-field').then(m => m.NumberField));
const Popover = defineAsyncComponent(() => import('@/Components/ui/popover').then(m => m.Popover));
const PopoverAnchor = defineAsyncComponent(() => import('@/Components/ui/popover').then(m => m.PopoverAnchor));
const PopoverContent = defineAsyncComponent(() => import('@/Components/ui/popover').then(m => m.PopoverContent));
const Switch = defineAsyncComponent(() => import('@/Components/ui/switch').then(m => m.Switch));

const props = defineProps<{
  element: NumberStatSection;
  anchorId?: number | null;
  band?: BandResolution;
  /** Full-screen editor mode: the optional title/subtitle/eyebrow header becomes
   *  click-to-edit. Undefined/false in every other context. */
  editable?: boolean;
  /** Declared (but unused) purely to intercept `BlockPreviewRenderer`'s generic
   *  `inlineEditable` fallthrough — this block has no per-field claiming, but an
   *  undeclared non-undefined prop would otherwise land on the root as a stray attribute. */
  blockKey?: string;
  /** @see blockKey */
  activeInlineField?: string | null;
}>();

const emit = defineEmits<(e: 'update:element', value: NumberStatSection) => void>();

const hotspots = inject(ACTIVE_HOTSPOT_KEY, undefined);
const statisticsRef = ref<HTMLElement | null>(null);
const currentStatisticIndex = ref(0);
const statisticHotspotId = computed(() => `${props.blockKey ?? ''}:statistic`);
const currentStatistic = computed(() => props.element.json_content[currentStatisticIndex.value]);

function updateOptions(patch: { title?: string; subtitle?: string; eyebrow?: string }): void {
  emit('update:element', { ...props.element, options: { ...props.element.options, ...patch } });
}

type Statistic = NumberStatSection['json_content'][number];

function updateStatistic(index: number, statistic: Statistic): void {
  const next = [...props.element.json_content];
  next[index] = statistic;
  emit('update:element', { ...props.element, json_content: next });
}

function removeStatistic(index: number): void {
  emit('update:element', { ...props.element, json_content: props.element.json_content.filter((_, statisticIndex) => statisticIndex !== index) });
  currentStatisticIndex.value = Math.max(0, Math.min(currentStatisticIndex.value, props.element.json_content.length - 2));
  hotspots?.close(statisticHotspotId.value);
}

function addStatistic(): void {
  emit('update:element', { ...props.element, json_content: [...props.element.json_content, { endNumber: 0, label: '' }] });
}

function openStatisticOptions(index: number): void {
  if (!props.editable) return;
  currentStatisticIndex.value = index;
  hotspots?.openPopover(statisticHotspotId.value);
}

function onStatisticPopoverOpenChange(open: boolean): void {
  if (open) hotspots?.openPopover(statisticHotspotId.value);
  else hotspots?.close(statisticHotspotId.value);
}
</script>
