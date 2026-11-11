<script setup lang="ts">
import { computed, onMounted, ref, toRef, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, BookOpen, Pencil } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { RecordActionMenu } from '@/Components/Patterns';
import { Sheet, SheetContent, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { enterKnowledgeRecord } from '@/Composables/useRecordTrail';
import LearningCompletionControl from '@/Features/Admin/Learning/LearningCompletionControl.vue';
import LearningAudienceNotes from '@/Features/Admin/Learning/LearningAudienceNotes.vue';
import LearningStepAction from '@/Features/Admin/Learning/LearningStepAction.vue';
import LearningStepGlossaryEditor, { type GlossaryTermOption } from '@/Features/Admin/Learning/LearningStepGlossaryEditor.vue';
import LearningTerms from '@/Features/Admin/Learning/LearningTerms.vue';
import { useStepProgress } from '@/Features/Admin/Learning/composables/useStepProgress';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { safeHref } from '@/Utils/safeHref';
import type { KnowledgeItemData, LearningCoordinator, LearningInstitutionContacts } from '@/Types/learning';

const props = withDefaults(defineProps<{
  item: KnowledgeItemData;
  inPath: boolean;
  coordinators: LearningCoordinator[];
  institutionContacts: LearningInstitutionContacts[];
  canUpdate: boolean;
  glossaryOptions?: GlossaryTermOption[];
}>(), { glossaryOptions: () => [] });
const readonly = computed(() => !props.inPath);
const { checked, saving, failed } = useStepProgress(toRef(props, 'item'), readonly);
const glossaryOpen = ref(false);
const isMobile = useMediaQuery('(max-width: 767px)');
const actions = computed<AdminRecordAction[]>(() => props.canUpdate ? [
  { key: 'edit', label: $t('Redaguoti'), icon: Pencil, href: route('knowledgeItems.edit', props.item.id) },
  { key: 'glossary', label: $t('learning.path.manage_terms'), icon: BookOpen },
] : []);
const { formatDate } = useDateFormatter();
const hasAction = computed(() => Boolean(safeHref(props.item.source?.url)));
const sourceHref = computed(() => safeHref(props.item.source?.url));
const sourceLabel = computed(() => {
  if (!props.item.source) return null;
  if (props.item.source.title) return props.item.source.title;
  try {
    return sourceHref.value ? new URL(sourceHref.value).hostname.replace(/^www\./, '') : null;
  }
  catch {
    return sourceHref.value ?? null;
  }
});
const thumbnailFailed = ref(false);
const thumbnailUrl = computed(() => thumbnailFailed.value ? null : props.item.source?.thumbnailUrl ?? null);
const enterTrail =() => enterKnowledgeRecord({ id: props.item.id, title: props.item.title, href: route('knowledgeItems.show', props.item.id) });
onMounted(enterTrail);
watch(() => props.item.id, () => {
  enterTrail();
  glossaryOpen.value = false;
  thumbnailFailed.value = false;
});
</script>

<template>
  <OverviewPage :title="item.title" class="gap-8! lg:gap-10! sm:[&>[data-slot=overview-title-band]]:items-start">
    <template #heading>
      <div class="grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-3" data-slot="step-heading">
        <LearningCompletionControl v-model="checked" :step="item" :disabled="saving || !inPath" class="mt-1.5 sm:mt-2.5" />
        <div class="min-w-0">
          <h1 class="text-balance text-3xl font-bold leading-tight text-foreground sm:text-4xl">{{ item.title }}</h1>
          <p class="mt-2 flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground" data-slot="step-meta">
            <span :class="item.importance === 'essential' ? 'font-semibold text-brand' : 'font-semibold text-foreground'">{{ $t(`learning.importance.${item.importance}`) }}</span>
            <template v-if="item.section">
              <span aria-hidden="true">·</span>
              <Link :href="route('knowledgeBase.show', { view: 'sections' }) + `#section-${item.section.id}`" class="hover:text-foreground">{{ item.section.title }}</Link>
            </template>
            <template v-if="item.perTerm">
              <span aria-hidden="true">·</span>
              <span>{{ $t('learning.path.per_term') }}</span>
            </template>
          </p>
          <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
        </div>
      </div>
    </template>
    <template v-if="hasAction || actions.length" #actions>
      <div class="flex flex-wrap items-center gap-2" data-slot="step-actions">
        <LearningStepAction :step="item" />
        <RecordActionMenu :actions="actions" trigger-size="compact" @select="key => key === 'glossary' && (glossaryOpen = true)" />
      </div>
    </template>

    <div class="grid gap-10 sm:pl-14 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-x-14" data-slot="step-body">
      <article class="min-w-0 max-w-prose lg:col-start-1 lg:row-start-1">
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="rc-prose" v-html="item.description" />
        <LearningAudienceNotes :notes="item.audienceNotes" />
      </article>

      <aside
        class="space-y-5 border-t border-border pt-6 lg:sticky lg:top-[calc(var(--shell-chrome-height,0px)+1.5rem)] lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
        aria-labelledby="step-about-title"
        data-slot="step-about"
      >
        <h2 id="step-about-title" class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.path.about_item') }}</h2>
        <div v-if="sourceLabel && thumbnailUrl" class="space-y-2" data-slot="source-preview">
          <p class="text-xs text-muted-foreground">{{ $t('learning.path.source') }}</p>
          <!-- Below lg the aside follows the article, so the page shrinks to a row instead of filling the screen. -->
          <component
            :is="sourceHref ? 'a' : 'div'"
            v-bind="sourceHref ? { href: sourceHref, target: '_blank', rel: 'noopener noreferrer' } : {}"
            class="group flex items-start gap-4 lg:block"
          >
            <span :class="['block aspect-3/4 w-24 shrink-0 overflow-hidden border border-border bg-muted transition-colors sm:w-28 lg:w-full', sourceHref && 'group-hover:border-brand group-focus-visible:border-brand']">
              <img :src="thumbnailUrl" alt="" loading="lazy" decoding="async" class="size-full object-cover object-top" @error="thumbnailFailed = true">
            </span>
            <span class="block min-w-0 space-y-1 lg:mt-2.5">
              <span :class="['block text-sm font-semibold leading-snug text-foreground', sourceHref && 'group-hover:text-brand']">
                <span :class="sourceHref && 'underline decoration-border underline-offset-4 group-hover:decoration-brand'">{{ sourceLabel }}</span>
                <ArrowUpRight v-if="sourceHref" class="ml-1 inline size-3.5 align-[-1px]" aria-hidden="true" />
              </span>
              <span v-if="item.source?.private" class="block text-xs text-muted-foreground">{{ $t('learning.path.document_platform_only') }}</span>
            </span>
          </component>
        </div>
        <div v-else-if="sourceLabel" class="space-y-1">
          <p class="text-xs text-muted-foreground">{{ $t('learning.path.source') }}</p>
          <a v-if="sourceHref" :href="sourceHref" target="_blank" rel="noopener noreferrer" class="group inline-flex items-start gap-1.5 text-sm font-semibold leading-snug text-foreground hover:text-brand">
            <span class="underline decoration-border underline-offset-4 group-hover:decoration-brand">{{ sourceLabel }}</span>
            <ArrowUpRight class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
          </a>
          <p v-else class="text-sm font-semibold leading-snug">{{ sourceLabel }}</p>
          <p v-if="item.source?.private" class="text-xs text-muted-foreground">{{ $t('learning.path.document_platform_only') }}</p>
          <p v-else-if="item.source?.type === 'document' && !sourceHref" class="text-xs text-muted-foreground">{{ $t('learning.path.document_private') }}</p>
        </div>
        <LearningTerms :terms="item.glossaryTerms" />
        <p class="text-xs text-muted-foreground">
          {{ item.reviewedAt ? $t('learning.path.reviewed_on', { date: formatDate(item.reviewedAt) }) : $t('learning.path.not_reviewed') }}
        </p>
      </aside>

      <div id="insights" class="min-w-0 scroll-mt-24 border-t border-border pt-6 lg:col-start-1 lg:row-start-2">
        <DiscussionPanel :key="item.id" commentable-type="knowledgeItem" :commentable-id="item.id" :title="$t('learning.path.insights')" />
      </div>
    </div>

    <Sheet v-if="canUpdate" v-model:open="glossaryOpen">
      <SheetContent :side="isMobile ? 'bottom' : 'right'" class="max-h-[92dvh] overflow-y-auto">
        <SheetHeader><SheetTitle>{{ $t('learning.path.manage_terms') }}</SheetTitle></SheetHeader>
        <div class="px-4 pb-6" data-slot="step-glossary-editor">
          <LearningStepGlossaryEditor :key="item.id" :step-id="item.id" :selected="item.glossaryTerms.map(term => ({ id: term.id, term: term.term }))" :options="glossaryOptions" />
        </div>
      </SheetContent>
    </Sheet>
  </OverviewPage>
</template>
