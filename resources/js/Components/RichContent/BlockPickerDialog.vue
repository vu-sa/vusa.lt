<template>
  <Dialog :open @update:open="$emit('update:open', $event)">
    <DialogContent
      class="top-auto bottom-0 left-0 flex h-[92dvh] w-full max-w-none translate-x-0 translate-y-0 flex-col gap-0 overflow-hidden border-border p-0 md:top-1/2 md:bottom-auto md:left-1/2 md:h-[min(82vh,46rem)] md:w-[min(96vw,86rem)] md:-translate-x-1/2 md:-translate-y-1/2 sm:max-w-none"
      :style="isMobile ? { top: 'auto', bottom: '0', left: '0', translate: 'none' } : undefined"
      :show-close-button="false"
    >
      <DialogHeader class="flex flex-row items-center gap-3 border-b border-border px-4 py-3 text-left">
        <Button v-if="mobilePreviewOpen" class="md:hidden" variant="ghost" size="icon" :aria-label="$t('rich-content.back_to_blocks')" @click="mobilePreviewOpen = false">
          <ArrowLeft class="size-4" />
        </Button>
        <DialogTitle class="text-base">
          {{ insertLabel ?? $t('rich-content.select_content_block') }}
        </DialogTitle>
        <DialogDescription class="sr-only">
          {{ $t('rich-content.select_content_block') }}
        </DialogDescription>
        <Button class="ml-auto" variant="ghost" size="icon" :aria-label="$t('rich-content.close_picker')" @click="emit('update:open', false)">
          <X class="size-4" />
        </Button>
      </DialogHeader>

      <div class="flex min-h-0 flex-1 flex-col md:flex-row">
        <!-- Category rail -->
        <div :class="mobilePreviewOpen ? 'hidden md:block' : ''" class="flex shrink-0 gap-1 overflow-x-auto border-b border-border bg-secondary/30 p-2 md:block md:w-44 md:space-y-0.5 md:overflow-y-auto md:border-r md:border-b-0">
          <button
            v-for="category in categoryList"
            :key="category.value"
            type="button"
            class="flex min-h-11 shrink-0 items-center justify-between gap-2 px-3 py-2 text-left text-sm transition-colors md:w-full"
            :class="activeCategory === category.value
              ? 'bg-accent font-medium text-foreground'
              : 'text-muted-foreground hover:bg-accent hover:text-foreground'"
            :aria-pressed="activeCategory === category.value"
            @click="activeCategory = category.value"
          >
            <span class="truncate">{{ category.label }}</span>
            <span class="text-xs text-muted-foreground">{{ category.count }}</span>
          </button>
        </div>

        <!-- Type list -->
        <div :class="mobilePreviewOpen ? 'hidden md:flex' : ''" class="flex min-h-0 min-w-0 flex-1 flex-col md:w-72 md:flex-none md:border-r md:border-border">
          <div class="border-b border-border p-2">
            <Input
              v-model="searchTerm"
              type="search"
              :aria-label="$t('rich-content.search_content_types')"
              :placeholder="$t('rich-content.search_content_types')"
            />
          </div>
          <div class="flex-1 space-y-0.5 overflow-y-auto p-1.5">
            <button
              v-for="type in filteredTypes"
              :key="type.value"
              type="button"
              class="flex min-h-11 w-full items-start gap-2 px-3 py-2.5 text-left transition-colors"
              :class="previewedType === type.value ? 'bg-accent' : 'hover:bg-accent/70'"
              :aria-current="previewedType === type.value ? 'true' : undefined"
              @mouseenter="previewedType = type.value"
              @focus="previewedType = type.value"
              @click="selectType(type.value)"
            >
              <component :is="type.icon" class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground" />
              <span class="min-w-0 flex-1">
                <span class="flex items-center gap-1.5">
                  <span class="truncate text-sm font-medium text-foreground">{{ type.label }}</span>
                  <Badge
                    v-if="type.isNew"
                    variant="success"
                    size="tiny"
                  >
                    {{ $t('rich-content.new_badge') }}
                  </Badge>
                  <Badge
                    v-if="type.inlineEditable"
                    variant="outline"
                    size="tiny"
                    class="shrink-0 gap-1 font-normal"
                    :title="$t('rich-content.fullscreen_editable_hint')"
                  >
                    <Maximize2 class="size-3 shrink-0" />
                    <span>{{ $t('rich-content.fullscreen_badge') }}</span>
                  </Badge>
                </span>
                <span v-if="type.description" class="line-clamp-1 text-xs text-muted-foreground">
                  {{ type.description }}
                </span>
              </span>
            </button>
            <p v-if="filteredTypes.length === 0" class="p-4 text-center text-sm text-muted-foreground">
              {{ $t('rich-content.no_content_types_found') }}
            </p>
          </div>
        </div>

        <!-- Live preview -->
        <div :class="mobilePreviewOpen ? 'flex' : 'hidden md:flex'" class="min-h-0 min-w-0 flex-1 flex-col overflow-hidden">
          <!-- Variant switcher — only for types whose render shape actually differs
               by variant (see contentSampleVariants); most types don't show this. -->
          <div v-if="previewVariants" class="flex flex-wrap gap-1.5 border-b border-border p-2">
            <button
              v-for="(variant, index) in previewVariants"
              :key="variant.label"
              type="button"
              class="min-h-11 px-3 py-2 text-xs font-medium transition-colors"
              :class="activeVariantIndex === index
                ? 'bg-primary text-primary-foreground'
                : 'bg-secondary text-secondary-foreground hover:bg-accent'"
              :aria-pressed="activeVariantIndex === index"
              @click="activeVariantIndex = index"
            >
              {{ variant.label }}
            </button>
          </div>
          <div class="min-h-0 flex-1 overflow-hidden bg-secondary/50 p-3 md:p-4">
            <div v-if="previewedContentType" class="h-full overflow-hidden border border-border bg-background">
              <div v-if="previewElement" class="relative h-full overflow-auto">
                <div
                  class="rc-canvas pointer-events-none origin-top-left"
                  :style="{ width: `${PREVIEW_WIDTH}px`, transform: `scale(${isMobile ? MOBILE_PREVIEW_SCALE : PREVIEW_SCALE})`, '--rc-measure': '40rem' }"
                >
                  <BlockPreviewRenderer :element="previewElement" :resolved="previewResolved" />
                </div>
              </div>
              <div v-else class="flex h-full flex-col items-center justify-center gap-2 p-8 text-center">
                <component :is="previewedContentType.icon" class="h-8 w-8 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                  {{ $t('rich-content.no_preview_available') }}
                </p>
              </div>
            </div>
            <div v-else class="flex h-full items-center justify-center text-sm text-muted-foreground">
              {{ $t('rich-content.hover_to_preview') }}
            </div>
          </div>
          <div v-if="previewedContentType" class="flex items-center justify-between gap-3 border-t border-border p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))]">
            <div class="min-w-0">
              <div class="flex items-center gap-1.5">
                <p class="truncate text-sm font-medium text-foreground">
                  {{ previewedContentType.label }}
                </p>
                <Badge
                  v-if="previewedContentType.isNew"
                  variant="success"
                  size="tiny"
                >
                  {{ $t('rich-content.new_badge') }}
                </Badge>
                <Badge
                  v-if="previewedContentType.inlineEditable"
                  variant="outline"
                  size="tiny"
                  class="shrink-0 gap-1 font-normal"
                  :title="$t('rich-content.fullscreen_editable_hint')"
                >
                  <Maximize2 class="size-3 shrink-0" />
                  <span>{{ $t('rich-content.fullscreen_badge') }}</span>
                </Badge>
              </div>
              <p v-if="previewedContentType.description" class="truncate text-xs text-muted-foreground">
                {{ previewedContentType.description }}
              </p>
            </div>
            <Button variant="brand" size="sm" @click="choose(previewedContentType.value)">
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
import { ArrowLeft, Maximize2, X } from 'lucide-vue-next';

import BlockPreviewRenderer from './Editor/BlockPreviewRenderer.vue';
import { getAllContentTypes, getContentType, type BlockCategory, type ContentType } from './Types';
import { getContentSample, getContentSampleVariants } from './Types/samples';

import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { useIsMobile } from '@/Composables/useIsMobile';

// Wide enough that section-chrome blocks (hero, card-stack, …) render at something
// close to their real proportions before being scaled down to fit the pane.
const PREVIEW_WIDTH = 1280;
const PREVIEW_SCALE = 0.5;
const MOBILE_PREVIEW_SCALE = 0.25;

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
const mobilePreviewOpen = ref(false);
const isMobile = useIsMobile();

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
    types = types.filter(t =>
      t.label.toLowerCase().includes(query)
      || t.description?.toLowerCase().includes(query)
      || (t.inlineEditable && ('ekranas'.includes(query) || 'fullscreen'.includes(query))),
    );
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
  mobilePreviewOpen.value = false;
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

function selectType(type: string) {
  previewedType.value = type;
  if (isMobile.value) {
    mobilePreviewOpen.value = true;
    return;
  }
  choose(type);
}
</script>
