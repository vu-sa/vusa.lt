<template>
  <section
    data-slot="dutiable-timeline-dirty-bar"
    class="flex h-full min-w-48 flex-col justify-center gap-2 overflow-y-auto p-3"
  >
    <p class="text-[10px] font-semibold uppercase tracking-wide text-muted-foreground">
      {{ $t('dutiables.timeline.dock.save') }}
    </p>

    <p v-if="!isDirty" class="text-xs text-muted-foreground">
      {{ $t('dutiables.timeline.staging.clean') }}
    </p>

    <template v-else>
      <p class="text-xs font-medium text-amber-700 dark:text-amber-300">
        <!--
          `dirty_count` is a trans_choice source string; `$t` on it printed the raw
          "{1} …|[2,9] …|[10,*] …" pipeline to the user.
        -->
        {{ $tChoice('dutiables.timeline.staging.dirty_count', dirtyCount, { count: dirtyCount }) }}
      </p>

      <div class="flex flex-col gap-1.5">
        <Button size="xs" :disabled="processing" @click="emit('save')">
          <Save class="size-3.5" />
          {{ processing ? $t('dutiables.timeline.staging.saving') : $t('dutiables.timeline.staging.save') }}
        </Button>
        <Button size="xs" variant="outline" :disabled="processing" @click="emit('preview')">
          <ListChecks class="size-3.5" />
          {{ $t('dutiables.timeline.staging.preview') }}
        </Button>
        <Button size="xs" variant="ghost" :disabled="processing" @click="emit('discard')">
          {{ $t('dutiables.timeline.staging.discard') }}
        </Button>
      </div>
    </template>

    <p v-if="syncPending" class="text-[10px] text-amber-600 dark:text-amber-400">
      {{ $t('dutiables.timeline.staging.sync_pending') }}
    </p>
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
