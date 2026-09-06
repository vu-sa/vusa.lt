<template>
  <div class="w-full">
    <!-- Full-screen editor modal -->
    <RCFullscreenEditor
      v-if="isFullscreenOpen"
      v-model:contents="contents"
      :tenant-id
      :history="{ commit, undo, redo, canUndo, canRedo }"
      @close="isFullscreenOpen = false"
      @save="$emit('save')"
    />

    <!-- Launcher Card (Primary Form View) -->
    <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-xs dark:border-zinc-800 dark:bg-zinc-900/50">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <h4 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
              {{ $t('rich-content.content') }}
            </h4>
            <Badge variant="secondary" size="sm">
              {{ (contents?.length ?? 0) === 0
                ? $t('rich-content.content_empty')
                : $tChoice('rich-content.blocks_count', contents?.length ?? 0) }}
            </Badge>
          </div>
          <p class="text-xs text-zinc-500 dark:text-zinc-400">
            {{ (contents?.length ?? 0) === 0
              ? $t('rich-content.content_empty_description')
              : $t('rich-content.fullscreen_editable_hint') }}
          </p>
        </div>

        <div class="flex items-center">
          <SpotlightPopover
            :title="$t('rich-content.spotlight.title')"
            :description="$t('rich-content.spotlight.description')"
            :is-dismissed="editorSpotlight.isDismissed.value"
            position="top-right"
            @dismiss="editorSpotlight.dismiss"
          >
            <Button type="button" @click="handleOpenEditor">
              <IFluentEdit24Regular class="mr-1.5 size-4" />
              {{ $t('rich-content.edit_content') }}
            </Button>
          </SpotlightPopover>
        </div>
      </div>

      <!-- Compact preview chips of existing blocks -->
      <div v-if="(contents?.length ?? 0) > 0" class="mt-3 flex flex-wrap gap-1.5 border-t border-zinc-100 pt-3 dark:border-zinc-800">
        <div
          v-for="(item, idx) in contents"
          :key="item?.id ?? item?.key ?? idx"
          class="flex items-center gap-1.5 rounded-md border border-zinc-200 bg-zinc-50 px-2 py-1 text-xs text-zinc-700 dark:border-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-300"
        >
          <component :is="getContentType(item.type).icon" class="size-3.5 text-zinc-500" />
          <span class="font-medium">{{ getContentType(item.type).label }}</span>
          <span v-if="deriveBlockSummary(item) !== '—'" class="max-w-[14rem] truncate text-zinc-400">
            · {{ deriveBlockSummary(item) }}
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { useManualRefHistory } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';

import RCFullscreenEditor from './Editor/Fullscreen/RCFullscreenEditor.vue';
import { deriveBlockSummary } from './Editor/blockSummary';
import { getContentType, type ContentPart } from './Types';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import IFluentEdit24Regular from '~icons/fluent/edit24-regular';

defineProps<{
  maxContentBlocks?: number;
  /** Tenant the page/news article being edited belongs to — for server-resolved previews. */
  tenantId?: number | null;
}>();

const contents = defineModel<ContentPart[]>('contents');

defineEmits<(e: 'save') => void>();

/**
 * Ensure all content items have unique keys.
 */
const ensureKeys = () => {
  if (!contents.value) return;
  contents.value.forEach((item, index) => {
    if (!item.key && !item.id) {
      item.key = `generated-${Date.now()}-${index}-${Math.random().toString(36).substring(7)}`;
    }
  });
};

onMounted(() => {
  ensureKeys();
});

const { commit, undo, redo, canUndo, canRedo } = useManualRefHistory(contents, { clone: true, capacity: 30 });

const isFullscreenOpen = ref(false);

const editorSpotlight = useFeatureSpotlight('rich-content-fullscreen-v1');

function handleOpenEditor(): void {
  editorSpotlight.dismiss();
  isFullscreenOpen.value = true;
}
</script>
