<script setup lang="ts">
import { computed, onMounted, ref, toRef, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ArrowUpRight, Pencil } from '@lucide/vue';

import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import OverviewPage from '@/Components/Layouts/OverviewPage.vue';
import { RecordActionMenu } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import { enterKnowledgeRecord } from '@/Composables/useRecordTrail';
import WikiProse from '@/Features/Admin/Knowledge/WikiProse.vue';
import WikiSubjectsSheet from '@/Features/Admin/Knowledge/WikiSubjectsSheet.vue';
import LearningCompletionControl from '@/Features/Admin/Learning/LearningCompletionControl.vue';
import LearningAudienceNotes from '@/Features/Admin/Learning/LearningAudienceNotes.vue';
import LearningStepAction from '@/Features/Admin/Learning/LearningStepAction.vue';
import { useStepProgress } from '@/Features/Admin/Learning/composables/useStepProgress';
import type { AdminRecordAction } from '@/Types/AdminRecordAction';
import { safeHref } from '@/Utils/safeHref';
import type { KnowledgeItemData, LearningCoordinator, LearningInstitutionContacts, LearningTypeOption, WikiPageSummary, WikiSubject } from '@/Types/learning';

const props = withDefaults(defineProps<{
  item: KnowledgeItemData;
  inPath: boolean;
  coordinators: LearningCoordinator[];
  institutionContacts: LearningInstitutionContacts[];
  canUpdate: boolean;
  linkedPages?: Record<string, WikiPageSummary>;
  related?: WikiPageSummary[];
  subjects?: WikiSubject[];
  subjectOptions?: { institutionTypes: LearningTypeOption[]; dutyTypes: LearningTypeOption[] };
}>(), { linkedPages: () => ({}), related: () => [], subjects: () => [], subjectOptions: () => ({ institutionTypes: [], dutyTypes: [] }) });

const readonly = computed(() => !props.inPath);
const { checked, saving, failed } = useStepProgress(toRef(props, 'item'), readonly);
const subjectsOpen = ref(false);
const actions = computed<AdminRecordAction[]>(() => props.canUpdate ? [
  { key: 'edit', label: $t('Redaguoti'), icon: Pencil, href: route('knowledgeItems.edit', props.item.id) },
] : []);
const { formatDate } = useDateFormatter();
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
const hasBody = computed(() => props.item.description.replace(/<[^>]*>/g, '').trim() !== '');
const enterTrail = () => enterKnowledgeRecord({ id: props.item.id, title: props.item.title, href: route('knowledgeItems.show', props.item.id) });
onMounted(enterTrail);
watch(() => props.item.id, () => {
  enterTrail();
  subjectsOpen.value = false;
  thumbnailFailed.value = false;
});
</script>

<template>
  <OverviewPage :title="item.title" :eyebrow="$t('learning.pages.knowledge')" class="gap-8! lg:gap-10! sm:[&>[data-slot=overview-title-band]]:items-start">
    <template #heading>
      <div :class="inPath ? 'grid grid-cols-[2.75rem_minmax(0,1fr)] gap-x-3' : ''" data-slot="step-heading">
        <LearningCompletionControl v-if="inPath" v-model="checked" :step="item" :disabled="saving" class="mt-1.5 sm:mt-2.5" />
        <div class="min-w-0">
          <h1 class="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-balance text-3xl font-bold leading-tight text-foreground sm:text-4xl">
            {{ item.title }}
            <abbr v-if="item.abbreviation" :title="item.title" class="border border-border px-2 py-0.5 text-base font-bold tracking-wide no-underline sm:text-lg" data-testid="article-abbreviation">{{ item.abbreviation }}</abbr>
          </h1>
          <p v-if="inPath || item.section || item.perTerm" class="mt-2 flex flex-wrap items-center gap-x-2 text-sm text-muted-foreground" data-slot="step-meta">
            <span v-if="inPath" :class="item.importance === 'essential' ? 'font-semibold text-brand' : 'font-semibold text-foreground'">{{ $t(`learning.importance.${item.importance}`) }}</span>
            <template v-if="item.section">
              <span v-if="inPath" aria-hidden="true">·</span>
              <Link :href="route('knowledgeBase.show') + `#section-${item.section.id}`" class="hover:text-foreground">{{ item.section.title }}</Link>
            </template>
            <template v-if="item.perTerm && inPath">
              <span aria-hidden="true">·</span>
              <span>{{ $t('learning.path.per_term') }}</span>
            </template>
          </p>
          <p v-if="item.summary" class="mt-4 max-w-prose text-lg leading-relaxed text-foreground" data-slot="article-summary">{{ item.summary }}</p>
          <p v-if="failed" role="alert" class="mt-2 text-sm text-destructive">{{ $t('learning.path.save_failed') }}</p>
        </div>
      </div>
    </template>
    <template v-if="sourceHref || actions.length" #actions>
      <div class="flex flex-wrap items-center gap-2" data-slot="step-actions">
        <LearningStepAction v-if="sourceHref" :step="item" />
        <RecordActionMenu :actions="actions" trigger-size="compact" />
      </div>
    </template>

    <div :class="['grid gap-10 lg:grid-cols-[minmax(0,1fr)_17rem] lg:gap-x-14', inPath && 'sm:pl-14']" data-slot="step-body">
      <article class="min-w-0 max-w-prose space-y-8 lg:col-start-1 lg:row-start-1">
        <WikiProse v-if="hasBody" :html="item.description" :pages="linkedPages" />
        <LearningAudienceNotes v-if="inPath" :notes="item.audienceNotes" />

        <section v-if="related.length" aria-labelledby="article-related" class="space-y-3 border-t border-border pt-6" data-slot="article-related">
          <div>
            <h2 id="article-related" class="text-xl font-bold leading-tight text-foreground">{{ $t('learning.wiki.related') }}</h2>
            <p class="mt-1 text-sm text-muted-foreground">{{ $t('learning.wiki.related_hint') }}</p>
          </div>
          <ul class="divide-y divide-border border-y border-border">
            <li v-for="page in related" :key="page.id" class="py-3">
              <Link :href="page.url" class="group block">
                <span class="flex flex-wrap items-baseline gap-x-2">
                  <span class="font-semibold text-foreground underline decoration-border underline-offset-4 group-hover:decoration-foreground">{{ page.title }}</span>
                  <span v-if="page.abbreviation" class="text-xs font-bold tracking-wide text-muted-foreground">{{ page.abbreviation }}</span>
                </span>
                <span v-if="page.summary" class="mt-0.5 line-clamp-2 block text-sm text-muted-foreground">{{ page.summary }}</span>
              </Link>
            </li>
          </ul>
        </section>
      </article>

      <aside
        class="space-y-6 border-t border-border pt-6 lg:sticky lg:top-[calc(var(--shell-chrome-height,0px)+1.5rem)] lg:col-start-2 lg:row-span-2 lg:row-start-1 lg:self-start lg:border-t-0 lg:border-l lg:pt-0 lg:pl-8"
        aria-labelledby="step-about-title"
        data-slot="step-about"
      >
        <h2 id="step-about-title" class="sr-only">{{ $t('learning.path.about_item') }}</h2>
        <div v-if="sourceLabel && thumbnailUrl" class="space-y-2" data-slot="source-preview">
          <p class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.path.source') }}</p>
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
          <p class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.path.source') }}</p>
          <a v-if="sourceHref" :href="sourceHref" target="_blank" rel="noopener noreferrer" class="group inline-flex items-start gap-1.5 text-sm font-semibold leading-snug text-foreground hover:text-brand">
            <span class="underline decoration-border underline-offset-4 group-hover:decoration-brand">{{ sourceLabel }}</span>
            <ArrowUpRight class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
          </a>
          <p v-else class="text-sm font-semibold leading-snug">{{ sourceLabel }}</p>
          <p v-if="item.source?.private" class="text-xs text-muted-foreground">{{ $t('learning.path.document_platform_only') }}</p>
          <p v-else-if="item.source?.type === 'document' && !sourceHref" class="text-xs text-muted-foreground">{{ $t('learning.path.document_private') }}</p>
        </div>

        <div v-if="subjects.length || canUpdate" class="space-y-2" data-slot="article-subjects">
          <div class="flex items-center justify-between gap-2">
            <p class="text-[11px] font-bold uppercase tracking-widest text-muted-foreground">{{ $t('learning.wiki.subjects') }}</p>
            <Button v-if="canUpdate" type="button" variant="ghost" voice="sentence" size="sm" class="-my-1" data-testid="edit-subjects" @click="subjectsOpen = true">{{ $t('learning.wiki.subjects_edit') }}</Button>
          </div>
          <ul v-if="subjects.length" class="space-y-1.5">
            <li v-for="subject in subjects" :key="`${subject.type}:${subject.id}`" class="text-sm leading-snug">
              <component :is="subject.url ? Link : 'span'" :href="subject.url ?? undefined" :class="['font-semibold text-foreground', subject.url && 'underline decoration-border underline-offset-4 hover:decoration-foreground']">{{ subject.title }}</component>
              <span class="block text-xs text-muted-foreground">{{ $t(`learning.wiki.subject_types.${subject.type}`) }}</span>
            </li>
          </ul>
          <p v-else class="text-xs text-muted-foreground">{{ $t('learning.wiki.subjects_empty') }}</p>
        </div>

        <p class="text-xs text-muted-foreground">
          {{ item.reviewedAt ? $t('learning.path.reviewed_on', { date: formatDate(item.reviewedAt) }) : $t('learning.path.not_reviewed') }}
        </p>
      </aside>

      <div id="insights" class="min-w-0 scroll-mt-24 border-t border-border pt-6 lg:col-start-1 lg:row-start-2">
        <DiscussionPanel :key="item.id" commentable-type="knowledgeItem" :commentable-id="item.id" :title="$t('learning.path.insights')" />
      </div>
    </div>

    <WikiSubjectsSheet
      v-if="canUpdate"
      v-model:open="subjectsOpen"
      :item-id="item.id"
      :subjects="subjects"
      :institution-types="subjectOptions.institutionTypes"
      :duty-types="subjectOptions.dutyTypes"
    />
  </OverviewPage>
</template>
