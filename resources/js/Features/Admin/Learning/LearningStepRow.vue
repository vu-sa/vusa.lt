<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed, toRef } from 'vue';
import { Eye, FileText, MessageSquare, MoreHorizontal } from '@lucide/vue';
import { trans as $t } from 'laravel-vue-i18n';

import { rowActionIconClass, rowActionHoverClass } from '@/Components/Collection/rowActionClasses';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';
import { safeHref } from '@/Utils/safeHref';

import LearningCoordinators from './LearningCoordinators.vue';
import LearningCompletionControl from './LearningCompletionControl.vue';
import LearningStepAction from './LearningStepAction.vue';
import LearningTerms from './LearningTerms.vue';
import { useStepProgress } from './composables/useStepProgress';

const props = defineProps<{
  step: LearningStepData;
  readonly?: boolean;
  coordinators?: LearningCoordinator[];
  institutionContacts?: LearningInstitutionContacts[];
  showGlossary?: boolean;
}>();
const { saving, failed, canSkip, checked, save } = useStepProgress(toRef(props, 'step'), toRef(props, 'readonly'));
const isDocument = computed(() => props.step.kind === 'knowledge');
const documentHref = computed(() => isDocument.value ? safeHref(props.step.source?.url) : undefined);
const isResolved = computed(() => props.step.state === 'done' || props.step.state === 'skipped');
const SUMMARY_KEYS = ['check_access', 'review_contacts', 'notification_settings', 'install_app', 'browser_push', 'orientation'];
const summary = computed(() => props.step.isSystem && SUMMARY_KEYS.includes(props.step.key ?? '') ? $t(`learning.system_steps.${props.step.key}.summary`) : props.step.description);
</script>

<template>
  <li :data-step="step.id" :aria-busy="saving" class="grid grid-cols-[2.75rem_minmax(0,1fr)_auto] items-start gap-x-3 px-4 py-4">
    <LearningCompletionControl v-model="checked" :step="step" :disabled="saving || readonly" />
    <div class="min-w-0">
      <div class="flex min-h-6 items-center gap-2 text-lg font-bold leading-6">
        <FileText v-if="isDocument" class="size-5 shrink-0 text-muted-foreground" role="img" :aria-label="$t('learning.path.kind_document')" />
        <a v-if="documentHref" :href="documentHref" target="_blank" rel="noopener noreferrer" :class="['underline decoration-border underline-offset-4 hover:decoration-foreground', isResolved ? 'text-muted-foreground' : 'text-foreground']">{{ step.title }}</a>
        <span v-else :class="isResolved ? 'text-muted-foreground' : 'text-foreground'">{{ step.title }}</span>
      </div>
      <div v-if="(isDocument && step.source?.type === 'document' && !documentHref) || step.state === 'updated' || step.state === 'skipped' || step.insightsCount > 0" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
        <span v-if="isDocument && step.source?.type === 'document' && !documentHref">{{ $t('learning.path.document_private') }}</span>
        <span v-if="step.state === 'updated'" class="font-semibold text-status-attention">{{ $t('learning.path.updated') }}</span>
        <span v-if="step.state === 'skipped'">{{ $t('learning.path.skipped') }}</span>
        <Link v-if="step.insightsCount > 0" :href="route('learningSteps.show', step.id) + '#insights'" class="inline-flex min-h-11 items-center gap-1 hover:text-foreground" :aria-label="$t('learning.path.insights_count', { count: String(step.insightsCount) })">
          <MessageSquare class="size-3.5" aria-hidden="true" />{{ step.insightsCount }}
        </Link>
      </div>
      <p v-if="summary" class="mt-1 line-clamp-2 max-w-prose whitespace-pre-line text-sm leading-relaxed text-muted-foreground">{{ summary }}</p>
      <LearningCoordinators v-if="step.key === 'meet_coordinator' && coordinators?.length" :coordinators="coordinators" class="mt-3" />
      <LearningTerms v-if="showGlossary" :terms="step.glossaryTerms" class="mt-2" />
      <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
    </div>
    <div class="col-start-2 row-start-2 flex min-w-0 flex-nowrap items-start gap-2 pt-3 sm:col-start-3 sm:row-start-1 sm:justify-end sm:-mt-1.5 sm:pt-0 sm:pointer-coarse:-mt-2.5">
      <LearningStepAction :step="step" />
      <Link :href="route('learningSteps.show', step.id)" :class="[rowActionIconClass, rowActionHoverClass]" :aria-label="$t('learning.path.open_step')" :title="$t('learning.path.open_step')">
        <Eye class="size-4" aria-hidden="true" />
      </Link>
      <DropdownMenu v-if="canSkip">
        <DropdownMenuTrigger as-child>
          <button type="button" :class="[rowActionIconClass, rowActionHoverClass]" :disabled="saving" :aria-label="$t('learning.path.actions')">
            <MoreHorizontal class="size-4" aria-hidden="true" />
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem data-testid="skip-step" @select="save('skipped')">{{ $t('learning.path.skip') }}</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <!-- Keeps the eye button in one column whether or not the step can be skipped. -->
      <span v-else class="hidden size-9 shrink-0 sm:block pointer-coarse:size-11" aria-hidden="true" />
    </div>
  </li>
</template>
