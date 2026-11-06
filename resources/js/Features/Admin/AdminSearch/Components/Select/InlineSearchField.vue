<template>
  <div class="space-y-1.5" data-slot="inline-search-field">
    <Label v-if="label" :for="inputId" class="text-sm font-medium">{{ label }}</Label>

    <div v-if="selected" class="flex items-center justify-between gap-3 border border-border bg-card p-2.5" :data-testid="selectedTestId">
      <slot name="selected" :hit="selected">
        <SearchHitRow :hit="selected" trailing="none" />
      </slot>
      <Button type="button" variant="ghost" size="sm" class="u-touch shrink-0" @click="emit('clear')">
        <X class="size-4" />
        <span class="sr-only">{{ clearLabel }}</span>
      </Button>
    </div>

    <slot v-else name="body">
      <div class="relative">
        <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          :id="inputId"
          v-model="query"
          role="combobox"
          class="pr-9 pl-9"
          autocomplete="off"
          aria-autocomplete="list"
          :aria-label="ariaLabel ?? label"
          :aria-expanded="showList"
          :aria-controls="listId"
          :aria-activedescendant="activeOptionId"
          :placeholder
          @keydown.down.prevent="move(1)"
          @keydown.up.prevent="move(-1)"
          @keydown.enter.prevent="chooseActive"
        />
        <Loader2 v-if="searching" class="absolute right-3 top-1/2 size-4 -translate-y-1/2 animate-spin text-muted-foreground" />
      </div>

      <!-- Stay in flow so the sheet body scrolls without clipping the results. -->
      <ul v-if="showList" :id="listId" role="listbox" :aria-label="$t('Paieškos rezultatai')" class="max-h-64 divide-y divide-border overflow-y-auto border border-border bg-card">
        <li
          v-for="(hit, index) in hits"
          :id="optionId(index)"
          :key="hit.id"
          role="option"
          :aria-selected="index === activeIndex"
          :aria-disabled="isDisabled?.(hit) ?? false"
          :class="[
            'flex min-h-11 cursor-pointer items-center justify-between gap-3 px-3 py-2',
            index === activeIndex && 'bg-accent',
            isDisabled?.(hit) ? 'cursor-not-allowed opacity-60' : 'hover:bg-accent',
          ]"
          tabindex="-1"
          @mousedown.prevent
          @click="choose(hit)"
          @keydown.enter.prevent="choose(hit)"
        >
          <SearchHitRow :hit="hit" :selected="index === activeIndex" trailing="none" />
        </li>
        <li
          v-if="hasMore"
          :id="optionId(hits.length)"
          role="option"
          :aria-selected="activeIndex === hits.length"
          :aria-disabled="loadingMore"
          :class="[
            'flex min-h-11 cursor-pointer items-center justify-center gap-2 px-3 py-2 text-sm hover:bg-accent',
            activeIndex === hits.length && 'bg-accent',
          ]"
          tabindex="-1"
          @mousedown.prevent
          @click="loadMore"
          @keydown.enter.prevent="loadMore"
        >
          <Loader2 v-if="loadingMore" class="size-4 animate-spin text-muted-foreground" aria-hidden="true" />
          {{ $t('Rodyti daugiau (:shown iš :total)', { shown: hits.length, total }) }}
        </li>
        <li v-if="hits.length === 0 && (searching || !hideEmpty)" class="px-3 py-4 text-center text-sm text-muted-foreground">
          {{ searching ? $t('Ieškoma…') : emptyMessage }}
        </li>
        <slot name="after-hits" />
      </ul>
    </slot>
    <p v-if="error" class="text-xs text-destructive">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2, Search, X } from '@lucide/vue';
import { computed, ref, useId, watch } from 'vue';

import SearchHitRow from '../SearchHitRow.vue';
import type { NormalizedSearchHit } from '../../Utils/searchHitMappers';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const props = defineProps<{
  label?: string;
  inputId?: string;
  ariaLabel?: string;
  placeholder?: string;
  hits: NormalizedSearchHit[];
  selected: NormalizedSearchHit | null;
  selectedTestId?: string;
  clearLabel: string;
  searching?: boolean;
  loadingMore?: boolean;
  hasMore?: boolean;
  total?: number;
  emptyMessage?: string;
  hideEmpty?: boolean;
  error?: string;
  isDisabled?: (hit: NormalizedSearchHit) => boolean;
}>();

const query = defineModel<string>('query', { required: true });
const emit = defineEmits<{
  choose: [hit: NormalizedSearchHit];
  loadMore: [];
  clear: [];
}>();

const uid = useId();
const inputId = computed(() => props.inputId ?? `inline-search-${uid}`);
const listId = `inline-search-list-${uid}`;
const optionId = (index: number) => `inline-search-option-${uid}-${index}`;
const activeIndex = ref(-1);
const showList = computed(() => query.value.trim().length >= 2);
const optionCount = computed(() => props.hits.length + (props.hasMore ? 1 : 0));
const activeOptionId = computed(() => activeIndex.value >= 0 && activeIndex.value < optionCount.value ? optionId(activeIndex.value) : undefined);

watch(query, () => {
  activeIndex.value = -1;
});

const move = (step: 1 | -1) => {
  if (!optionCount.value) {
    return;
  }
  activeIndex.value = activeIndex.value < 0
    ? (step === 1 ? 0 : optionCount.value - 1)
    : (activeIndex.value + step + optionCount.value) % optionCount.value;
};

const choose = (hit: NormalizedSearchHit) => {
  if (!props.isDisabled?.(hit)) {
    emit('choose', hit);
  }
};
const loadMore = () => {
  if (!props.loadingMore) {
    emit('loadMore');
  }
};
const chooseActive = () => {
  if (activeIndex.value === props.hits.length && props.hasMore) {
    loadMore();
    return;
  }
  const hit = props.hits[activeIndex.value];
  if (hit) {
    choose(hit);
  }
};
</script>
