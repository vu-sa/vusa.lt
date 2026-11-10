<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import StatusBadge from '@/Components/Patterns/StatusBadge.vue';
import { Button } from '@/Components/ui/button';
import { learningImportanceStatuses } from '@/Constants/statusIcons';
import type { LearningPathData } from '@/Types/learning';

import LearningStepAction from './LearningStepAction.vue';

const props = defineProps<{ documents: LearningPathData['documents']; readonly?: boolean }>();
const percent = computed(() => props.documents.essentialTotal ? props.documents.essentialDone / props.documents.essentialTotal * 100 : 0);
</script>

<template>
  <section v-if="documents.total > 0" class="space-y-3 border-y border-border py-5" data-slot="documents-entry">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
      <h2 class="text-xl font-bold sm:text-2xl">{{ $t('learning.pages.documents') }}</h2>
      <p class="text-sm font-semibold tabular-nums">{{ $t('learning.documents.essential_progress') }} {{ documents.essentialDone }}/{{ documents.essentialTotal }}</p>
    </div>
    <div class="h-1 bg-muted" role="progressbar" :aria-valuenow="percent" :aria-valuemin="0" :aria-valuemax="100" :aria-label="$t('learning.documents.essential_progress')">
      <div class="h-full bg-foreground" :style="{ width: `${percent}%` }" />
    </div>
    <div v-if="documents.nextDocument" class="space-y-2" data-slot="next-document">
      <p class="text-xs text-muted-foreground">{{ $t('learning.documents.next') }}</p>
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
          <Link :href="route('learningSteps.show', documents.nextDocument.id)" class="text-sm font-semibold hover:text-brand">{{ documents.nextDocument.title }}</Link>
          <StatusBadge :status="learningImportanceStatuses[documents.nextDocument.importance]" voice="sentence" />
        </div>
        <LearningStepAction :step="documents.nextDocument" />
      </div>
    </div>
    <Button as-child variant="brand" voice="sentence" class="min-h-11" data-slot="all-documents">
      <Link :href="route('learningPath.documents')">{{ $t('learning.documents.all') }}</Link>
    </Button>
  </section>
</template>
