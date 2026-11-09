<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';
import { ArrowUpRight, ChevronDown, FileText, ListChecks, MessageSquare, MoreHorizontal, Zap } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

import LearningStepContent from './LearningStepContent.vue';
import { useStepProgress } from './composables/useStepProgress';

const props = defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
  showGlossary?: boolean;
}>();

const { saving, failed, canSkip, checked, save } = useStepProgress(toRef(props, 'step'), toRef(props, 'readonly'));

/** Only these system steps carry widgets or contacts worth unfolding; every other row is complete as shown. */
const DETAIL_KEYS = ['install_app', 'browser_push', 'meet_coordinator', 'meet_institution_contacts'];
const hasDetails = computed(() => props.step.isSystem && DETAIL_KEYS.includes(props.step.key ?? ''));
const isDocument = computed(() => props.step.kind === 'knowledge');
const documentHref = computed(() => (isDocument.value ? safeHref(props.step.source?.url) : undefined));
const actionHref = computed(() => (!isDocument.value ? safeHref(props.step.actionUrl) : undefined));
const actionLabel = computed(() => (props.step.isSystem && props.step.key
  ? `learning.system_steps.${props.step.key}.action`
  : 'learning.path.open'));
const isResolved = computed(() => props.step.state === 'done' || props.step.state === 'skipped');
</script>

<template>
  <li
    :data-step="step.id"
    :aria-busy="saving"
    class="grid grid-cols-[2.75rem_minmax(0,1fr)_2.75rem] gap-x-2 py-3 sm:grid-cols-[2.75rem_minmax(0,1fr)_auto_2.75rem] sm:gap-x-4"
  >
    <div class="flex h-11 items-center">
      <span v-if="step.autoCompleted" class="flex size-11 items-center pl-0.5">
        <Checkbox :model-value="true" disabled class="disabled:opacity-100" :aria-label="step.title" data-testid="step-checkbox" />
      </span>
      <label v-else class="flex size-11 cursor-pointer items-center pl-0.5">
        <Checkbox v-model="checked" :disabled="saving || readonly" :aria-label="step.title" data-testid="step-checkbox" />
      </label>
    </div>

    <div class="min-w-0 pt-2.5">
      <p class="text-sm font-semibold leading-6">
        <a
          v-if="documentHref"
          :href="documentHref"
          target="_blank"
          rel="noopener noreferrer"
          :class="['decoration-border underline underline-offset-4 hover:decoration-foreground', isResolved ? 'text-muted-foreground' : 'text-foreground']"
        >{{ step.title }}</a>
        <span v-else :class="isResolved ? 'text-muted-foreground' : 'text-foreground'">{{ step.title }}</span>
      </p>

      <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span class="inline-flex items-center gap-1">
          <component :is="isDocument ? FileText : ListChecks" class="size-3.5" aria-hidden="true" />
          {{ $t(isDocument ? 'learning.path.kind_document' : 'learning.path.kind_task') }}
        </span>
        <span v-if="step.importance === 'essential'" class="font-semibold text-foreground">{{ $t('learning.importance.essential') }}</span>
        <span v-else-if="step.importance === 'optional'">{{ $t('learning.path.optional') }}</span>
        <span v-if="isDocument && step.source?.type === 'document' && !documentHref">{{ $t('learning.path.document_private') }}</span>
        <span v-if="step.state === 'updated'" class="font-semibold text-status-attention">{{ $t('learning.path.updated') }}</span>
        <span v-if="step.state === 'skipped'">{{ $t('learning.path.skipped') }}</span>
        <span v-if="step.autoCompleted" class="inline-flex items-center gap-1">
          <Zap class="size-3.5" role="img" :aria-label="$t('learning.path.auto_help')" />{{ $t('learning.path.auto_label') }}
        </span>
        <Link
          v-if="step.insightsCount > 0"
          :href="route('learningSteps.show', step.id) + '?tab=insights'"
          class="inline-flex items-center gap-1 hover:text-foreground"
          :aria-label="$t('learning.path.insights_count', { count: String(step.insightsCount) })"
        >
          <MessageSquare class="size-3.5" aria-hidden="true" />{{ step.insightsCount }}
        </Link>
      </div>

      <p v-if="step.description" class="mt-1.5 line-clamp-2 max-w-prose text-sm leading-relaxed text-muted-foreground">
        {{ step.description }}
      </p>

      <div v-if="showGlossary && step.glossaryTerms.length" class="mt-2 flex flex-wrap gap-1.5">
        <Popover v-for="term in step.glossaryTerms" :key="term.id">
          <PopoverTrigger as-child>
            <Button variant="outline" size="sm" voice="sentence" class="h-8 max-w-full whitespace-normal">{{ term.term }}</Button>
          </PopoverTrigger>
          <PopoverContent class="max-w-[calc(100vw-2rem)]">
            <p class="font-semibold">{{ term.term }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-muted-foreground">{{ term.definition }}</p>
            <Link :href="route('glossaryTerms.index') + '#term-' + term.id" class="inline-flex min-h-11 items-center text-sm underline underline-offset-4">{{ $t('learning.pages.glossary') }}</Link>
          </PopoverContent>
        </Popover>
      </div>

      <details v-if="hasDetails" class="group mt-1" data-slot="step-details">
        <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1 text-sm font-medium text-foreground focus-visible:outline-2 focus-visible:outline-ring">
          {{ $t('learning.path.more') }}<ChevronDown class="size-4 transition-transform group-open:rotate-180" aria-hidden="true" />
        </summary>
        <div class="pb-2">
          <LearningStepContent :step="step" details-only :readonly="readonly" :coordinators="coordinators" :institution-contacts="institutionContacts" />
        </div>
      </details>

      <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
    </div>

    <div v-if="documentHref || actionHref" class="col-start-2 row-start-2 pt-2 sm:col-start-3 sm:row-start-1 sm:flex sm:h-11 sm:items-center sm:pt-0">
      <Button v-if="documentHref" as-child variant="outline" size="sm" voice="sentence" class="h-auto min-h-11 whitespace-normal text-left sm:min-h-9">
        <a :href="documentHref" target="_blank" rel="noopener noreferrer" :aria-label="`${$t('learning.path.read_document')}: ${step.title}`">
          {{ $t('learning.path.read_document') }}<ArrowUpRight class="size-4 shrink-0" aria-hidden="true" />
        </a>
      </Button>
      <Button v-else-if="actionHref" as-child variant="outline" size="sm" voice="sentence" class="h-auto min-h-11 whitespace-normal text-left sm:min-h-9">
        <Link :href="actionHref">{{ $t(actionLabel) }}</Link>
      </Button>
    </div>

    <div class="col-start-3 row-start-1 flex h-11 items-center justify-end sm:col-start-4">
      <DropdownMenu v-if="canSkip">
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" size="icon" voice="sentence" class="size-11 shrink-0" :disabled="saving" :aria-label="$t('learning.path.actions')">
            <MoreHorizontal class="size-4" aria-hidden="true" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem data-testid="skip-step" @select="save('skipped')">{{ $t('learning.path.skip') }}</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  </li>
</template>
