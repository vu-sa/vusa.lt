<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CheckCheck, FileText } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import type { LearningPathData } from '@/Types/learning';

import LearningStepAction from './LearningStepAction.vue';

const props = defineProps<{ documents: LearningPathData['knowledge']; readonly?: boolean }>();
const percent = computed(() => props.documents.essentialTotal ? Math.round(props.documents.essentialDone / props.documents.essentialTotal * 100) : 0);
const next = computed(() => props.documents.nextItem);
</script>

<template>
  <section
    v-if="documents.total > 0"
    class="grid border border-border md:grid-cols-[17rem_minmax(0,1fr)_auto]"
    data-slot="documents-entry"
    aria-labelledby="documents-entry-title"
  >
    <div class="p-5">
      <h2 id="documents-entry-title" class="text-xl font-bold leading-tight">{{ $t('learning.pages.knowledge') }}</h2>
      <p class="mt-3 flex items-baseline gap-2 text-sm text-muted-foreground">
        <span class="u-display text-3xl leading-none tabular-nums text-foreground">{{ documents.essentialDone }}<span class="text-muted-foreground">/{{ documents.essentialTotal }}</span></span>
        {{ $t('learning.documents.essential_read') }}
      </p>
      <div class="mt-3 h-1.5 bg-muted" role="progressbar" :aria-valuenow="percent" :aria-valuemin="0" :aria-valuemax="100" :aria-label="$t('learning.documents.essential_progress')">
        <div class="h-full bg-brand-fill transition-[width]" :style="{ width: `${percent}%` }" />
      </div>
    </div>

    <div class="flex min-w-0 flex-col justify-center gap-2 border-t border-border p-5 md:border-t-0 md:border-l" data-slot="next-document">
      <template v-if="next">
        <p class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.documents.next') }}</p>
        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3">
          <div class="flex min-w-0 items-start gap-2">
            <FileText class="mt-0.5 size-5 shrink-0 text-muted-foreground" aria-hidden="true" />
            <div class="min-w-0">
              <Link :href="route('knowledgeItems.show', next.id)" class="font-semibold leading-snug text-foreground underline-offset-4 hover:text-brand hover:underline">{{ next.title }}</Link>
              <p :class="['mt-0.5 text-xs', next.importance === 'essential' ? 'font-semibold text-brand' : 'text-muted-foreground']">{{ $t(`learning.importance.${next.importance}`) }}</p>
            </div>
          </div>
          <LearningStepAction :step="next" />
        </div>
      </template>
      <p v-else class="flex items-center gap-2 text-sm text-muted-foreground">
        <CheckCheck class="size-4 shrink-0 text-foreground" aria-hidden="true" />{{ $t('learning.documents.all_essential_read') }}
      </p>
    </div>

    <div class="flex items-center border-t border-border p-5 md:border-t-0 md:border-l">
      <Button as-child variant="brand" voice="sentence" class="w-full md:w-auto" data-slot="all-documents">
        <Link :href="route('knowledgeBase.show')">{{ $t('learning.documents.open') }}<ArrowRight class="size-4" aria-hidden="true" /></Link>
      </Button>
    </div>
  </section>
</template>
