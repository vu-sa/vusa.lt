<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCheck, FileText } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import WikiAssignedProgress from '@/Features/Admin/Knowledge/WikiAssignedProgress.vue';
import type { LearningPathData } from '@/Types/learning';

import LearningStepAction from './LearningStepAction.vue';

const props = defineProps<{ documents: LearningPathData['knowledge']; readonly?: boolean }>();
const next = computed(() => props.documents.nextItem);
</script>

<template>
  <section
    v-if="documents.total > 0"
    class="grid items-center gap-6 md:grid-cols-[15rem_minmax(0,1fr)_auto] md:grid-rows-[auto_auto] md:gap-y-2"
    data-slot="documents-entry"
    aria-labelledby="documents-entry-title"
  >
    <div class="flex flex-col gap-2 md:row-span-2 md:grid md:grid-rows-subgrid md:self-stretch md:py-4">
      <h2 id="documents-entry-title" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.documents.progress') }}</h2>
      <WikiAssignedProgress :progress="documents.priorityProgress" :show-legend="false" />
    </div>

    <div class="flex min-w-0 flex-col gap-2 border border-border p-4 md:row-span-2 md:grid md:grid-rows-subgrid md:items-center md:self-stretch" data-slot="next-document">
      <template v-if="next">
        <p class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.documents.next') }}</p>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
          <div class="flex min-w-0 items-start gap-2">
            <FileText class="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
            <div class="min-w-0">
              <Link :href="route('knowledgeItems.show', next.id)" class="font-semibold leading-snug text-foreground underline decoration-border underline-offset-4 hover:decoration-foreground">{{ next.title }}</Link>
              <p :class="['mt-0.5 text-xs', next.importance === 'essential' ? 'font-semibold text-brand' : 'text-muted-foreground']">{{ $t(`learning.importance.${next.importance}`) }}</p>
            </div>
          </div>
          <LearningStepAction :step="next" class="w-36 justify-center" />
        </div>
      </template>
      <p v-else class="flex items-center gap-2 text-sm text-muted-foreground md:row-start-2">
        <CheckCheck class="size-4 shrink-0 text-foreground" aria-hidden="true" />{{ $t('learning.documents.all_essential_read') }}
      </p>
    </div>

    <div class="flex items-center md:row-span-2 md:grid md:grid-rows-subgrid md:self-stretch md:py-4">
      <Button as-child variant="brand" voice="sentence" size="sm" class="w-36 md:row-start-2 md:self-center" data-slot="all-documents">
        <Link :href="route('knowledgeBase.mine')">{{ $t('learning.documents.open') }}<ArrowRight class="size-4" aria-hidden="true" /></Link>
      </Button>
    </div>
  </section>
</template>
