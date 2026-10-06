<template>
  <SpotlightPopover
    :title="$t('editor.recovery_title')"
    :description="$t('editor.recovery_hint')"
    :is-dismissed="spotlight.isDismissed.value"
    @dismiss="spotlight.dismiss"
  >
    <button
      type="button"
      class="flex min-h-11 shrink-0 items-center gap-2 text-left text-xs text-muted-foreground"
      data-slot="content-recovery-status"
      :title="`${$t(`editor.recovery_${editor.status.value}`)} ${$t('editor.private_only')}.`"
      @click="spotlight.dismiss()"
    >
      <component
        :is="statusIcon"
        :class="[
          'size-4 shrink-0',
          editor.status.value === 'syncing' && 'animate-spin',
          needsAttention && 'text-[var(--status-attention)]',
        ]"
        aria-hidden="true"
      />
      <span role="status" aria-live="polite">
        {{ $t(editor.copies.value.length ? 'editor.status_recovery' : `editor.status_${editor.status.value}`) }}
      </span>
    </button>
  </SpotlightPopover>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { CircleAlert, CloudCheck, CloudOff, GitCompareArrows, HardDrive, Loader2, ShieldCheck } from 'lucide-vue-next';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { useContentEditor } from '@/Composables/useContentEditor';

const props = defineProps<{ editor: ReturnType<typeof useContentEditor> }>();

const spotlight = useFeatureSpotlight('content-editor-recovery-v1');

const needsAttention = computed(() => ['offline', 'conflict', 'unavailable'].includes(props.editor.status.value));

const statusIcon = computed(() => ({
  idle: ShieldCheck,
  local: HardDrive,
  syncing: Loader2,
  synced: CloudCheck,
  offline: CloudOff,
  conflict: GitCompareArrows,
  unavailable: CircleAlert,
})[props.editor.status.value]);
</script>
