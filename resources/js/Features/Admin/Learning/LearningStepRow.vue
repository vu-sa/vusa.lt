<script setup lang="ts">
import { Link as InertiaLink } from '@inertiajs/vue3';
import { computed, toRef, ref, watch, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { ArrowUpRight, ChevronDown, FileText, ArrowRight, MessageSquare, MoreHorizontal } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import type { LearningCoordinator, LearningInstitutionContacts, LearningStepData } from '@/Types/learning';
import LearningTerms from './LearningTerms.vue';
import { trans as $t } from 'laravel-vue-i18n';
import { safeHref } from '@/Utils/safeHref';

import LearningCoordinators from './LearningCoordinators.vue';
import LearningCompletionControl from './LearningCompletionControl.vue';
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

// These steps have widgets or contacts beyond their description.
const DETAIL_KEYS = ['install_app', 'browser_push', 'meet_institution_contacts'];
const hasWidgets = computed(() => props.step.isSystem && DETAIL_KEYS.includes(props.step.key ?? ''));
const isDocument = computed(() => props.step.kind === 'knowledge');
const documentHref = computed(() => (isDocument.value ? safeHref(props.step.source?.url) : undefined));
const actionHref = computed(() => (!isDocument.value ? safeHref(props.step.actionUrl) : undefined));
const actionLabel = computed(() => (props.step.isSystem && props.step.key
  ? `learning.system_steps.${props.step.key}.action`
  : 'learning.path.open'));
const isResolved = computed(() => props.step.state === 'done' || props.step.state === 'skipped');
const SUMMARY_KEYS = ['check_access', 'review_contacts', 'notification_settings', 'install_app', 'browser_push', 'orientation'];
const summary = computed(() => props.step.isSystem && SUMMARY_KEYS.includes(props.step.key ?? '') ? $t(`learning.system_steps.${props.step.key}.summary`) : props.step.description);
const expanded = ref(false);
const description = ref<HTMLElement>();
const overflows = ref(false);
const hasDetails = computed(() => hasWidgets.value || overflows.value || summary.value !== props.step.description);
let observer: ResizeObserver | undefined;
function measureDescription() {
  if (!expanded.value && description.value) overflows.value = description.value.scrollHeight > description.value.clientHeight;
}
onMounted(() => {
  if (typeof ResizeObserver !== 'undefined') {
    observer = new ResizeObserver(measureDescription);
    if (description.value) observer.observe(description.value);
  }
  measureDescription();
});
onBeforeUnmount(() => observer?.disconnect());
watch(() => props.step.description, async () => {
  expanded.value = false;
  await nextTick();
  measureDescription();
});
</script>

<template>
  <li
    :data-step="step.id"
    :aria-busy="saving"
    :class="['grid grid-cols-[2.75rem_minmax(0,1fr)_auto] items-start gap-x-2 px-3 py-4 sm:gap-x-4 sm:px-4', 'sm:grid-cols-[2.75rem_minmax(0,1fr)_auto_2.75rem]']"
  >
    <LearningCompletionControl v-model="checked" :step="step" :disabled="saving || readonly" />

    <div class="min-w-0">
      <div class="flex flex-wrap items-start gap-x-6 gap-y-3">
        <div class="min-w-0 flex-1 basis-72">
        <div class="flex min-h-6 items-center gap-1">
        <p class="flex min-w-0 items-center gap-2 text-lg font-bold leading-snug">
          <FileText v-if="isDocument" class="size-5 shrink-0 text-muted-foreground" role="img" :aria-label="$t('learning.path.kind_document')" />
          <a
            v-if="documentHref"
            :href="documentHref"
            target="_blank"
            rel="noopener noreferrer"
            :class="['decoration-border underline underline-offset-4 hover:decoration-foreground', isResolved ? 'text-muted-foreground' : 'text-foreground']"
          >{{ step.title }}</a>
          <span v-else :class="isResolved ? 'text-muted-foreground' : 'text-foreground'">{{ step.title }}</span>
        </p>
        </div>

        <div v-if="(isDocument && step.source?.type === 'document' && !documentHref) || step.state === 'updated' || step.state === 'skipped' || step.insightsCount > 0" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
          <span v-if="isDocument && step.source?.type === 'document' && !documentHref">{{ $t('learning.path.document_private') }}</span>
          <span v-if="step.state === 'updated'" class="font-semibold text-status-attention">{{ $t('learning.path.updated') }}</span>
          <span v-if="step.state === 'skipped'">{{ $t('learning.path.skipped') }}</span>
          <InertiaLink
            v-if="step.insightsCount > 0"
            :href="route('learningSteps.show', step.id) + '?tab=insights'"
            class="inline-flex items-center gap-1 hover:text-foreground"
            :aria-label="$t('learning.path.insights_count', { count: String(step.insightsCount) })"
          >
            <MessageSquare class="size-3.5" aria-hidden="true" />{{ step.insightsCount }}
          </InertiaLink>
        </div>

        <p v-if="step.description" ref="description" :class="['mt-1 max-w-prose whitespace-pre-line text-sm leading-relaxed text-muted-foreground', !expanded && 'line-clamp-2']">
          {{ expanded ? step.description : summary }}
        </p>

        </div>
        <LearningCoordinators v-if="step.key === 'meet_coordinator' && coordinators?.length" :coordinators="coordinators" class="min-w-0 flex-1 basis-64" />
      </div>

      <LearningTerms v-if="showGlossary" :terms="step.glossaryTerms" class="mt-2" />
      <details v-if="hasDetails" class="group" data-slot="step-details" @toggle="expanded = ($event.target as HTMLDetailsElement).open">
        <summary class="inline-flex min-h-11 cursor-pointer list-none items-center gap-1 text-sm font-medium focus-visible:outline-2 focus-visible:outline-ring">
          {{ $t('learning.path.more') }}<ChevronDown class="size-4 transition-transform group-open:rotate-180" aria-hidden="true" />
        </summary>
        <div v-if="hasWidgets" class="pb-2">
          <LearningStepContent :step="step" details-only :readonly="readonly" :coordinators="coordinators" :institution-contacts="institutionContacts" />
        </div>
      </details>

      <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
    </div>

    <div v-if="documentHref || actionHref" class="col-start-2 row-start-2 pt-2 sm:col-start-3 sm:row-start-1 sm:flex sm:h-6 sm:items-center sm:pt-0">
      <Button v-if="documentHref" as-child variant="default" size="sm" voice="sentence" class="h-auto min-h-11 whitespace-normal text-left sm:min-h-9">
        <a :href="documentHref" target="_blank" rel="noopener noreferrer" :aria-label="`${$t('learning.path.read_document')}: ${step.title}`">
          {{ $t('learning.path.read_document') }}<ArrowUpRight class="size-4 shrink-0" aria-hidden="true" />
        </a>
      </Button>
      <Button v-else-if="actionHref" as-child variant="default" size="sm" voice="sentence" class="h-auto min-h-11 whitespace-normal text-left sm:min-h-9">
        <InertiaLink :href="actionHref"><ArrowRight class="size-4" aria-hidden="true" />{{ $t(actionLabel) }}</InertiaLink>
      </Button>
    </div>

    <div v-if="canSkip" class="col-start-3 row-start-1 flex h-6 items-center justify-end sm:col-start-4">
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
