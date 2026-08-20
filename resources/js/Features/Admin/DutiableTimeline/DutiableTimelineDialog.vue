<template>
  <Dialog :open="open" @update:open="emit('update:open', $event)">
    <!-- `DialogContent`, not the scrolling variant: the chart and the dock do their own
         scrolling, and an outer scroller would unstick the dock. -->
    <DialogContent class="flex h-[90vh] flex-col gap-3 sm:max-w-[95vw]">
      <DialogHeader>
        <DialogTitle>{{ $t('dutiables.timeline.title') }}</DialogTitle>
        <DialogDescription>{{ $t('dutiables.timeline.description') }}</DialogDescription>
      </DialogHeader>

      <!-- Mounted only while open so the chart never renders into a hidden container,
           where every width measurement would be zero. -->
      <DutiableTimelineEditor
        v-if="open"
        class="min-h-0 flex-auto"
        :scope-type="scopeType"
        :scope-id="scopeId"
      />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';

import DutiableTimelineEditor from './DutiableTimelineEditor.vue';
import type { TimelineScopeType } from './types';

defineProps<{
  open: boolean;
  scopeType: TimelineScopeType;
  scopeId: string;
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();
</script>
