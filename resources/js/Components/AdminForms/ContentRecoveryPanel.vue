<template>
  <div v-if="!hideStatus || hasDetails" data-testid="content-recovery">
    <ContentRecoveryStatus v-if="!hideStatus" :editor />
    <p
      v-if="needsAttention && !editor.copies.value.length"
      :class="['pb-2 text-xs leading-relaxed text-muted-foreground', hideStatus ? 'px-3 pt-2' : 'pl-6']"
    >
      {{ $t(`editor.recovery_${editor.status.value}`) }}
    </p>
    <div v-if="editor.copies.value.length" :class="['space-y-3 border-border py-4', hideStatus ? 'border-b px-3' : 'border-t']">
      <div class="flex items-start gap-3">
        <RotateCcw class="mt-0.5 size-5 shrink-0 text-foreground" aria-hidden="true" />
        <div class="space-y-1">
          <p class="text-sm font-semibold">
            {{ $t('editor.recovery_available_title') }}
          </p>
          <p class="text-xs leading-relaxed text-muted-foreground">
            {{ $t('editor.recovery_available') }}
          </p>
        </div>
      </div>
      <div class="grid gap-3 sm:grid-cols-2">
        <div
          v-for="(copy, index) in editor.copies.value"
          :key="index"
          class="flex items-start gap-3 border border-border bg-muted/30 p-3"
        >
          <component
            :is="copy.source === 'local' ? HardDrive : Cloud"
            class="mt-1 size-4 shrink-0 text-muted-foreground"
            aria-hidden="true"
          />
          <div class="min-w-0 flex-1 space-y-1">
            <p class="text-sm font-medium">
              {{ $t(`editor.copy_${copy.source}`) }}
            </p>
            <p v-if="copy.snapshot.title" class="truncate text-xs text-muted-foreground">
              {{ copy.snapshot.title }}
            </p>
            <time class="block text-xs text-muted-foreground" :datetime="copy.updated_at">
              {{ formatDateTime(copy.updated_at) }}
            </time>
            <Button type="button" variant="outline" size="sm" class="mt-2 min-h-11" @click="editor.restore(index); spotlight.dismiss()">
              <RotateCcw class="size-3.5" aria-hidden="true" />
              {{ $t('editor.restore') }}
            </Button>
          </div>
        </div>
      </div>
      <Button
        type="button"
        variant="ghost"
        size="sm"
        class="min-h-11 text-muted-foreground"
        @click="discardOpen = true"
      >
        <Trash2 class="size-3.5" aria-hidden="true" />
        {{ $t('editor.discard') }}
      </Button>
    </div>
    <div
      v-if="editor.saveError.value"
      :class="['flex items-start gap-2 border-border py-3 text-sm text-destructive', hideStatus ? 'border-b px-3' : 'border-t']"
      role="alert"
    >
      <CircleAlert class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
      <p>{{ editor.saveError.value }}</p>
    </div>
    <Button
      v-if="editor.recordConflict.value"
      type="button"
      variant="outline"
      :class="['mb-2 min-h-11', hideStatus && 'mx-3 mt-2']"
      @click="editor.loadCurrent()"
    >
      <GitCompareArrows class="size-4" aria-hidden="true" />
      {{ $t('editor.resolve_conflict') }}
    </Button>
  </div>
  <ConfirmDialog
    v-model:open="discardOpen"
    :title="$t('editor.discard_title')"
    :description="$t('editor.discard_hint')"
    :confirm-label="$t('editor.discard')"
    destructive
    @confirm="editor.discard()"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { CircleAlert, Cloud, GitCompareArrows, HardDrive, RotateCcw, Trash2 } from 'lucide-vue-next';

import ContentRecoveryStatus from './ContentRecoveryStatus.vue';

import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import type { useContentEditor } from '@/Composables/useContentEditor';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { formatDateTime } from '@/Utils/dateTime';

const props = defineProps<{
  editor: ReturnType<typeof useContentEditor>;
  /** The caller renders `ContentRecoveryStatus` itself; only what needs a decision shows here. */
  hideStatus?: boolean;
}>();

const spotlight = useFeatureSpotlight('content-editor-recovery-v1');
const discardOpen = ref(false);

const needsAttention = computed(() => ['offline', 'conflict', 'unavailable'].includes(props.editor.status.value));

const hasDetails = computed(() => needsAttention.value
  || props.editor.copies.value.length > 0
  || Boolean(props.editor.saveError.value)
  || props.editor.recordConflict.value);
</script>
