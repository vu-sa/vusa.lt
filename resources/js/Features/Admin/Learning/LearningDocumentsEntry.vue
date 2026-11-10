<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { Button } from '@/Components/ui/button';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import type { LearningPathData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

const props = defineProps<{ documents: LearningPathData['documents']; readonly?: boolean }>();
const percent = computed(() => props.documents.essentialTotal ? props.documents.essentialDone / props.documents.essentialTotal * 100 : 0);
const { isDismissed, dismiss } = useFeatureSpotlight('learning-documents-v1');
</script>

<template>
  <section v-if="documents.total > 0" class="space-y-3 border-y border-border py-5" data-slot="documents-entry">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 class="text-xl font-bold sm:text-2xl">{{ $t('learning.pages.documents') }}</h2>
      <span class="text-sm tabular-nums text-muted-foreground">{{ $t('learning.documents.read_total') }} {{ documents.done }}/{{ documents.total }}</span>
    </div>
    <p class="text-sm font-semibold tabular-nums">{{ $t('learning.documents.essential_progress') }} {{ documents.essentialDone }}/{{ documents.essentialTotal }}</p>
    <div class="h-1 bg-muted" role="progressbar" :aria-valuenow="percent" :aria-valuemin="0" :aria-valuemax="100" :aria-label="$t('learning.path.summary_essential')">
      <div class="h-full bg-foreground" :style="{ width: `${percent}%` }" />
    </div>
    <ul class="divide-y divide-border">
      <li v-for="step in documents.next.slice(0, 2)" :key="step.id">
        <a v-if="safeHref(step.source?.url)" :href="safeHref(step.source?.url)" target="_blank" rel="noopener noreferrer" class="flex min-h-11 items-center gap-2 py-2 text-sm underline underline-offset-4">{{ step.title }}<ArrowRight class="size-4 shrink-0" aria-hidden="true" /></a>
        <Link v-else :href="route('learningSteps.show', step.id)" class="flex min-h-11 items-center gap-2 py-2 text-sm underline underline-offset-4">{{ step.title }}<ArrowRight class="size-4 shrink-0" aria-hidden="true" /></Link>
      </li>
    </ul>
    <SpotlightPopover :is-dismissed="readonly || isDismissed" :title="$t('learning.documents.spotlight_title')" :description="$t('learning.documents.spotlight_description')" @dismiss="dismiss">
      <Button as-child variant="outline" voice="sentence" class="min-h-11">
        <Link :href="route('learningPath.documents')" @click="dismiss">{{ $t('learning.documents.all') }}</Link>
      </Button>
    </SpotlightPopover>
  </section>
</template>
