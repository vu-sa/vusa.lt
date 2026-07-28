<template>
  <Dialog :open="open" @update:open="$emit('update:open', $event)">
    <DialogContent class="w-[min(96vw,86rem)] max-w-none sm:max-w-none gap-0 overflow-hidden p-0">
      <DialogHeader class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
        <DialogTitle class="text-base">
          {{ insertLabel ?? $t('rich-content.select_content_block') }}
        </DialogTitle>
        <DialogDescription class="sr-only">
          {{ $t('rich-content.select_content_block') }}
        </DialogDescription>
      </DialogHeader>

      <div class="flex h-[min(78vh,42rem)]">
        <!-- Category rail -->
        <div class="w-44 shrink-0 space-y-0.5 overflow-y-auto border-r border-zinc-100 bg-zinc-50/50 p-2 dark:border-zinc-800 dark:bg-zinc-900/30">
          <button
            v-for="category in categoryList"
            :key="category.value"
            type="button"
            class="flex w-full items-center justify-between rounded-md px-2.5 py-1.5 text-left text-sm transition-colors"
            :class="activeCategory === category.value
              ? 'bg-zinc-200 font-medium text-zinc-900 dark:bg-zinc-700 dark:text-zinc-100'
              : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-800'"
            @click="activeCategory = category.value"
          >
            <span class="truncate">{{ category.label }}</span>
            <span class="text-xs text-zinc-400">{{ category.count }}</span>
          </button>
        </div>

        <!-- Type list -->
        <div class="flex w-72 shrink-0 flex-col border-r border-zinc-100 dark:border-zinc-800">
          <div class="border-b border-zinc-100 p-2 dark:border-zinc-800">
            <Input
              v-model="searchTerm"
              type="search"
              class="h-8 text-sm"
              :placeholder="$t('rich-content.search_content_types')"
            />
          </div>
          <div class="flex-1 space-y-0.5 overflow-y-auto p-1.5">
            <button
              v-for="type in filteredTypes"
              :key="type.value"
              type="button"
              class="flex w-full items-start gap-2 rounded-md px-2 py-2 text-left transition-colors"
              :class="previewedType === type.value ? 'bg-zinc-100 dark:bg-zinc-800' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/50'"
              @mouseenter="previewedType = type.value"
              @focus="previewedType = type.value"
              @click="choose(type.value)"
            >
              <component :is="type.icon" class="mt-0.5 h-4 w-4 shrink-0 text-zinc-500" />
              <span class="min-w-0 flex-1">
                <span class="flex items-center gap-1.5">
                  <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ type.label }}</span>
                  <Badge v-if="type.isNew" variant="success" size="tiny">
                    {{ $t('rich-content.new_badge') }}
                  </Badge>
                </span>
                <span v-if="type.description" class="line-clamp-1 text-xs text-zinc-500 dark:text-zinc-400">
                  {{ type.description }}
                </span>
              </span>
            </button>
            <p v-if="filteredTypes.length === 0" class="p-4 text-center text-sm text-zinc-400">
              {{ $t('rich-content.no_content_types_found') }}
            </p>
          </div>
        </div>

        <!-- Live preview -->
        <div class="flex flex-1 flex-col overflow-hidden">
          <!-- Variant switcher — only for types whose render shape actually differs
               by variant (see contentSampleVariants); most types don't show this. -->
          <div v-if="previewVariants" class="flex flex-wrap gap-1.5 border-b border-zinc-100 p-2 dark:border-zinc-800">
            <button
              v-for="(variant, index) in previewVariants"
              :key="variant.label"
              type="button"
              class="rounded-full px-2.5 py-1 text-xs font-medium transition-colors"
              :class="activeVariantIndex === index
                ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900'
                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-400 dark:hover:bg-zinc-700'"
              @click="activeVariantIndex = index"
            >
              {{ variant.label }}
            </button>
          </div>
          <div class="flex-1 overflow-hidden bg-zinc-100 p-4 dark:bg-zinc-950">
            <div v-if="previewedContentType" class="h-full overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
              <div v-if="previewElement" class="relative h-full overflow-auto">
                <div
                  class="rc-canvas pointer-events-none origin-top-left"
                  :style="{ width: `${PREVIEW_WIDTH}px`, transform: `scale(${PREVIEW_SCALE})`, '--rc-measure': '40rem' }"
                >
                  <BlockPreviewRenderer :element="previewElement" :resolved="previewResolved" />
                </div>
              </div>
              <div v-else class="flex h-full flex-col items-center justify-center gap-2 p-8 text-center">
                <component :is="previewedContentType.icon" class="h-8 w-8 text-zinc-300 dark:text-zinc-600" />
                <p class="text-sm text-zinc-400">
                  {{ $t('rich-content.no_preview_available') }}
                </p>
              </div>
            </div>
            <div v-else class="flex h-full items-center justify-center text-sm text-zinc-400">
              {{ $t('rich-content.hover_to_preview') }}
            </div>
          </div>
          <div v-if="previewedContentType" class="flex items-center justify-between gap-3 border-t border-zinc-100 p-3 dark:border-zinc-800">
            <div class="min-w-0">
              <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                {{ previewedContentType.label }}
              </p>
              <p v-if="previewedContentType.description" class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                {{ previewedContentType.description }}
              </p>
            </div>
            <Button size="sm" @click="choose(previewedContentType.value)">
              {{ $t('rich-content.add_this_block') }}
            </Button>
          </div>
        </div>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import BlockPreviewRenderer from './Editor/BlockPreviewRenderer.vue';
import { getAllContentTypes, getContentType, type BlockCategory, type ContentType } from './Types';
import { getContentSample, getContentSampleVariants } from './Types/samples';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';

// Wide enough that section-chrome blocks (hero, card-stack, …) render at something
// close to their real proportions before being scaled down to fit the pane.
const PREVIEW_WIDTH = 1280;
const PREVIEW_SCALE = 0.5;

const CATEGORY_ORDER: BlockCategory[] = ['text', 'media', 'section', 'embed', 'special'];

const props = defineProps<{
  open: boolean;
  /** Overrides the dialog title, e.g. "Insert after block 2" when inserting mid-list. */
  insertLabel?: string;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'select', type: string): void;
}>();

const searchTerm = ref('');
const activeCategory = ref<BlockCategory | 'all'>('all');
const previewedType = ref<string | null>(null);

const allTypes = getAllContentTypes();

const categoryList = computed(() => [
  { value: 'all' as const, label: $t('rich-content.category_all'), count: allTypes.length },
  ...CATEGORY_ORDER.map(category => ({
    value: category,
    label: $t(`rich-content.category_${category}`),
    count: allTypes.filter(t => t.category === category).length,
  })),
]);

const filteredTypes = computed(() => {
  let types = allTypes;
  if (activeCategory.value !== 'all') {
    types = types.filter(t => t.category === activeCategory.value);
  }
  const query = searchTerm.value.trim().toLowerCase();
  if (query) {
    types = types.filter(t => t.label.toLowerCase().includes(query) || t.description?.toLowerCase().includes(query));
  }
  return types;
});

const previewedContentType = computed<ContentType | null>(() => (previewedType.value ? getContentType(previewedType.value) : null));

// Types whose render shape actually differs by variant (currently just `hero`) get
// multiple named previews instead of always showing the default one.
const previewVariants = computed(() => (previewedContentType.value ? getContentSampleVariants(previewedContentType.value.value) : null));
const activeVariantIndex = ref(0);

const previewSample = computed(() => {
  if (previewVariants.value) {
    return previewVariants.value[activeVariantIndex.value]?.sample() ?? null;
  }
  return previewedContentType.value ? getContentSample(previewedContentType.value.value) : null;
});

const previewElement = computed(() => {
  if (!previewedContentType.value || !previewSample.value) return null;
  return {
    type: previewedContentType.value.value,
    json_content: previewSample.value.json_content,
    options: previewSample.value.options ?? previewedContentType.value.defaultOptions?.() ?? {},
  };
});

// Fabricated preview payload for `serverResolved` types (link-list, event-list) — the
// picker never hits the network, so this stands in for what `useContentPartPreview`
// would otherwise fetch.
const previewResolved = computed(() => previewSample.value?.resolved);

// Reset to a clean slate each time the dialog opens, and keep the preview in sync
// with whatever the current filtered list's first result is.
watch(() => props.open, (isOpen) => {
  if (!isOpen) return;
  searchTerm.value = '';
  activeCategory.value = 'all';
  previewedType.value = filteredTypes.value[0]?.value ?? null;
});

watch(filteredTypes, (types) => {
  if (!types.some(t => t.value === previewedType.value)) {
    previewedType.value = types[0]?.value ?? null;
  }
});

// A stale variant index (e.g. "panel" selected on hero) shouldn't carry over to a
// different type's variant list, or to a type with none at all.
watch(previewedType, () => {
  activeVariantIndex.value = 0;
});

function choose(type: string) {
  emit('select', type);
  emit('update:open', false);
}
</script>
