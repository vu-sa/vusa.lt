<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { ChevronDown, FileText, ListChecks, MessageSquare } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

import LearningStepContent from './LearningStepContent.vue';
import LearningStepProgress from './LearningStepProgress.vue';

const props = defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
  showGlossary?: boolean;
}>();
const hasDetails = computed(() => props.step.isSystem && ['install_app', 'browser_push', 'meet_coordinator', 'meet_institution_contacts'].includes(props.step.key ?? ''));
</script>

<template>
  <div class="border-b border-border py-2" :data-step="step.id">
    <LearningStepProgress :step="step" :readonly="readonly">
      <div class="flex min-h-11 flex-wrap items-center gap-x-2 gap-y-1 py-2 text-sm">
        <component :is="step.kind === 'knowledge' ? FileText : ListChecks" class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
        <a v-if="step.kind === 'knowledge' && safeHref(step.source?.url)" :href="safeHref(step.source?.url)" target="_blank" rel="noopener noreferrer" class="min-w-0 break-words font-semibold underline underline-offset-4">{{ step.title }}</a>
        <span v-else class="min-w-0 break-words font-semibold">{{ step.title }}</span>
        <span v-if="step.kind === 'knowledge' && step.source?.type === 'document' && !safeHref(step.source.url)" class="text-xs text-muted-foreground">{{ $t('learning.path.document_private') }}</span>
        <Link v-if="step.kind === 'action' && safeHref(step.actionUrl)" :href="safeHref(step.actionUrl)!" class="inline-flex min-h-11 items-center underline underline-offset-4">{{ $t(step.isSystem && step.key ? `learning.system_steps.${step.key}.action` : 'learning.path.open') }}</Link>
        <span v-if="step.importance === 'essential'" class="text-xs text-muted-foreground">{{ $t('learning.importance.essential') }}</span>
        <span v-else-if="step.importance === 'optional'" class="text-xs text-muted-foreground">{{ $t('learning.path.optional') }}</span>
        <span v-if="step.state === 'updated'" class="text-xs text-muted-foreground">{{ $t('learning.path.updated') }}</span>
        <Link :href="route('learningSteps.show', step.id) + '?tab=insights'" class="inline-flex min-h-11 items-center gap-1 text-xs text-muted-foreground hover:text-foreground" :aria-label="$t('learning.path.insights_count', { count: String(step.insightsCount) })">
          <MessageSquare class="size-3.5" aria-hidden="true" /><span>{{ step.insightsCount }}</span>
        </Link>
      </div>
      <p v-if="step.description" class="line-clamp-2 max-w-prose whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ step.description }}</p>
      <div v-if="showGlossary && step.glossaryTerms.length" class="flex flex-wrap gap-2 pt-2">
        <Popover v-for="term in step.glossaryTerms" :key="term.id">
          <PopoverTrigger as-child>
            <Button variant="outline" voice="sentence" class="min-h-11 max-w-full whitespace-normal">{{ term.term }}</Button>
          </PopoverTrigger>
          <PopoverContent class="max-w-[calc(100vw-2rem)]">
            <p class="font-semibold">{{ term.term }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-muted-foreground">{{ term.definition }}</p>
            <Link :href="route('glossaryTerms.index') + '#term-' + term.id" class="inline-flex min-h-11 items-center text-sm underline underline-offset-4">{{ $t('learning.pages.glossary') }}</Link>
          </PopoverContent>
        </Popover>
      </div>
      <details v-if="hasDetails" class="pt-2" data-slot="step-details">
        <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 text-sm text-muted-foreground focus-visible:outline-2 focus-visible:outline-ring">
          {{ $t('learning.path.more') }}<ChevronDown class="size-4" aria-hidden="true" />
        </summary>
        <LearningStepContent :step="step" details-only :readonly="readonly" :coordinators="coordinators" :institution-contacts="institutionContacts" />
      </details>
    </LearningStepProgress>
  </div>
</template>
