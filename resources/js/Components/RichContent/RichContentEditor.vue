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
    <div class="border border-border bg-background p-4">
      <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <h4 class="text-sm font-bold text-foreground">
              {{ $t('rich-content.content') }}
            </h4>
            <span class="inline-flex items-center border border-border bg-secondary/60 px-2 py-0.5 text-xs font-medium normal-case text-muted-foreground">
              {{ (contents?.length ?? 0) === 0
                ? $t('rich-content.content_empty')
                : $tChoice('rich-content.blocks_count', contents?.length ?? 0) }}
            </span>
          </div>
          <p class="text-xs leading-relaxed text-muted-foreground">
            {{ (contents?.length ?? 0) === 0
              ? $t('rich-content.content_empty_description')
              : $t('rich-content.fullscreen_editable_hint') }}
          </p>
        </div>

        <div class="flex items-center">
          <Button type="button" variant="outline" class="gap-1.5" @click="isFullscreenOpen = true">
            <Pencil class="size-4" />
            {{ $t('rich-content.edit_content') }}
          </Button>
        </div>
      </div>

      <!-- Compact preview chips of existing blocks -->
      <div v-if="(contents?.length ?? 0) > 0" class="mt-3 flex flex-wrap gap-1.5 border-t border-border pt-3">
        <div
          v-for="(item, idx) in contents"
          :key="item?.id ?? item?.key ?? idx"
          class="flex items-center gap-1.5 border border-border bg-secondary/40 px-2.5 py-1 text-xs text-foreground"
        >
          <component :is="getContentType(item.type).icon" class="size-3.5 text-muted-foreground" />
          <span class="font-medium">{{ getContentType(item.type).label }}</span>
          <span v-if="deriveBlockSummary(item) !== '—'" class="max-w-[14rem] truncate text-muted-foreground">
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
import { Pencil } from 'lucide-vue-next';

import RCFullscreenEditor from './Editor/Fullscreen/RCFullscreenEditor.vue';
import { deriveBlockSummary } from './Editor/blockSummary';
import { getContentType, type ContentPart } from './Types';

import { Button } from '@/Components/ui/button';

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
</script>
