<template>
  <!--
    In the toolbar rather than a dock under the chart: "you have unsaved changes" has to be
    visible wherever the chart is scrolled, and the toolbar never scrolls away.
  -->
  <section
    data-slot="dutiable-timeline-dirty-bar"
    :data-dirty="isDirty || undefined"
    class="flex flex-wrap items-center gap-2"
    :aria-label="$t('dutiables.timeline.dock.save')"
  >
    <p v-if="syncPending" class="text-xs text-status-attention">
      {{ $t('dutiables.timeline.staging.sync_pending') }}
    </p>

    <p v-if="!isDirty" class="text-xs text-muted-foreground">
      {{ $t('dutiables.timeline.staging.clean') }}
    </p>

    <template v-else>
      <!--
        `dirty_count` is a trans_choice source string; `$t` on it printed the raw
        "{1} …|[2,9] …|[10,*] …" pipeline to the user.
      -->
      <p class="text-xs font-medium text-status-attention">
        {{ $tChoice('dutiables.timeline.staging.dirty_count', dirtyCount, { count: dirtyCount }) }}
      </p>

      <Button size="xs" variant="ghost" class="pointer-coarse:min-h-11" :disabled="processing" @click="emit('discard')">
        {{ $t('dutiables.timeline.staging.discard') }}
      </Button>
      <Button size="xs" variant="outline" class="pointer-coarse:min-h-11" :disabled="processing" @click="emit('preview')">
        <ListChecks class="size-3.5" />
        {{ $t('dutiables.timeline.staging.preview') }}
      </Button>
      <Button size="xs" variant="brand" class="pointer-coarse:min-h-11" :disabled="processing" @click="emit('save')">
        <Save class="size-3.5" />
        {{ processing ? $t('dutiables.timeline.staging.saving') : $t('dutiables.timeline.staging.save') }}
      </Button>
    </template>
  </section>
</template>

<script setup lang="ts">
import { ListChecks, Save } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

defineProps<{
  dirtyCount: number;
  isDirty: boolean;
  processing: boolean;
  syncPending: boolean;
}>();

const emit = defineEmits<{
  preview: [];
  discard: [];
  save: [];
}>();
</script>
