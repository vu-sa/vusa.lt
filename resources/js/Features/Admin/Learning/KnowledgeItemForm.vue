<template>
  <FormPage
    :title="pageTitle"
    :bar-title="barTitle"
    required-note
    :back-href="backHref"
    :back-label="$t('learning.pages.knowledge_library')"
    :dirty="form.isDirty"
    :processing="form.processing"
    :errors="form.errors"
    :field-ids="fieldIds"
    :mode="isEdit ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts="missingLocaleCounts"
    @update:locale="activeLocale = $event"
    @submit="submit"
  >
    <FormFieldWrapper
      id="step-title"
      :label="$t('learning_library.fields.title')"
      :hint="$t('learning_library.fields.knowledge_title_hint')"
      :error="form.errors[`title.${activeLocale}`] || form.errors.title"
      :required="activeLocale === 'lt'"
    >
      <Input id="step-title" v-model="form.title[activeLocale]" :class="['h-11', fieldSurfaceClass]" data-testid="step-title" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="article-abbreviation"
      :label="$t('learning_library.fields.abbreviation')"
      :hint="$t('learning_library.fields.abbreviation_hint')"
      :error="form.errors[`abbreviation.${activeLocale}`]"
    >
      <Input id="article-abbreviation" v-model="form.abbreviation[activeLocale]" maxlength="32" :class="['h-11 max-w-48', fieldSurfaceClass]" data-testid="article-abbreviation" />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="article-summary"
      :label="$t('learning_library.fields.summary')"
      :hint="$t('learning_library.fields.summary_hint')"
      :error="form.errors[`summary.${activeLocale}`] || form.errors.summary"
      :required="activeLocale === 'lt'"
    >
      <div class="space-y-1">
        <Textarea id="article-summary" v-model="form.summary[activeLocale]" rows="2" :maxlength="SUMMARY_MAX" :class="fieldSurfaceClass" data-testid="article-summary" />
        <p class="text-right text-xs tabular-nums text-muted-foreground" aria-hidden="true">{{ form.summary[activeLocale].length }}/{{ SUMMARY_MAX }}</p>
      </div>
    </FormFieldWrapper>

    <FormFieldWrapper
      id="step-description"
      :label="$t('learning_library.fields.description')"
      :hint="$t('learning_library.fields.knowledge_description_hint')"
      :error="form.errors[`description.${activeLocale}`] || form.errors.description"
    >
      <div class="space-y-2">
        <SpotlightPopover
          :title="$t('learning.wiki.link_spotlight_title')"
          :description="$t('learning.wiki.link_spotlight_body')"
          :is-dismissed="linkSpotlight.isDismissed.value"
          @dismiss="linkSpotlight.dismiss()"
        >
          <p class="inline-flex items-center gap-2 text-xs text-muted-foreground" data-slot="wiki-link-hint">
            <kbd class="border border-border bg-background px-1.5 font-mono text-[11px] font-bold text-foreground">[[</kbd>
            {{ $t('learning.wiki.link_button') }}
          </p>
        </SpotlightPopover>
        <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html :extensions="[wikiLinks()]" data-testid="step-description" />
      </div>
    </FormFieldWrapper>

    <LearningSourceField v-model="form.sources" :locale="activeLocale" :errors="form.errors" :documents="item?.source_documents" />

    <LearningAudienceList
      v-if="canManageAudiences"
      v-model="form.audiences"
      kind="knowledge"
      :locale="activeLocale"
      :errors="form.errors"
      :audience-types="audienceTypes"
      :can-target-everywhere="canTargetEverywhere"
      :tenants="tenants"
      :institution-types="institutionTypes"
      :duty-types="dutyTypes"
      :responsibilities="responsibilities"
    />

    <template #aside>
      <FormPanel :title="$t('learning_library.sections.placement')" :icon="SlidersHorizontal" data-testid="panel-additional">
        <FormFieldWrapper id="article-tenant" :label="$t('learning_library.fields.page_tenant')" :hint="$t('learning_library.fields.page_tenant_hint')" :error="form.errors.tenant_id">
          <NativeSelect
            id="article-tenant"
            v-model="form.tenant_id"
            :placeholder-value="null"
            :placeholder-selectable="canPublishEverywhere || item?.tenant_id === null"
            :options="pageTenants.map(tenant => ({ value: tenant.id, label: tenant.shortname }))"
            :placeholder="$t('learning.wiki.scope_everywhere')"
            data-testid="article-tenant"
          />
        </FormFieldWrapper>
        <FormFieldWrapper id="step-section" :label="$t('learning_library.fields.section')" :hint="$t('learning_library.fields.section_hint')" :error="form.errors.knowledge_section_id">
          <SingleSelect v-if="sectionOptions.length > 16" v-model="selectedSection" :options="sectionOptions" label-field="title" value-field="id" :placeholder="$t('learning_library.audiences.no_section')" />
          <NativeSelect v-else id="step-section" v-model="form.knowledge_section_id" :placeholder-value="null" placeholder-selectable :options="sectionOptions.map(section => ({ value: section.id, label: section.title }))" :placeholder="$t('learning_library.audiences.no_section')" data-testid="step-section" />
        </FormFieldWrapper>

        <FormFieldWrapper id="step-order" :label="$t('learning_library.fields.order')" :hint="$t('learning_library.fields.knowledge_order_hint')" :error="form.errors.order">
          <Input id="step-order" v-model.number="form.order" type="number" min="0" :class="['max-w-32', fieldSurfaceClass]" data-testid="step-order" />
        </FormFieldWrapper>
      </FormPanel>
      <FormPanel :title="$t('learning_library.sections.visibility')" :icon="Eye" flush>
        <FormToggleRow id="step-is-active" v-model="form.is_active" :label="$t('learning_library.fields.is_active')" :hint="$t('learning_library.fields.knowledge_is_active_hint')" data-testid="step-is-active" />
      </FormPanel>
    </template>

    <template v-if="isEdit && canDelete" #danger-zone>
      <Button type="button" variant="destructive" voice="sentence" data-testid="delete-learning-step" @click="deleteDialogOpen = true">
        <Trash2 class="size-4" aria-hidden="true" />
        {{ $t('learning_library.delete.knowledge_action') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-if="isEdit && canDelete"
    v-model:open="deleteDialogOpen"
    :title="$t('learning_library.delete.knowledge_title')"
    :description="$t('learning_library.delete.knowledge_description')"
    :confirm-label="$t('learning_library.delete.confirm')"
    destructive
    @confirm="destroy"
  />
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Eye, SlidersHorizontal, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormToggleRow } from '@/Components/Patterns';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { createWikiLinkExtension } from '@/Features/Admin/Knowledge/wikiLinkExtension';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { LearningAudienceType } from '@/Types/enums';
import type { KnowledgeItemEditData, LearningTenantOption, LearningTypeOption } from '@/Types/learning';

import LearningAudienceList from './LearningAudienceList.vue';
import LearningSourceField, { type LearningSourceDraft } from './LearningSourceField.vue';
import type { LearningAudienceDraft, LearningSectionOption } from './types';
import { hasRichText } from './composables/hasRichText';

type LocaleKey = 'lt' | 'en';

interface KnowledgeItemFormState {
  title: { lt: string; en: string };
  abbreviation: { lt: string; en: string };
  summary: { lt: string; en: string };
  description: { lt: string; en: string };
  sources: LearningSourceDraft;
  audiences: LearningAudienceDraft[];
  knowledge_section_id: string | null;
  tenant_id: number | null;
  order: number;
  is_active: boolean;
}

const props = withDefaults(defineProps<{
  item?: KnowledgeItemEditData | null;
  audienceTypes?: LearningAudienceType[];
  canTargetEverywhere?: boolean;
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  responsibilities?: { id: string; title: string }[];
  sections?: LearningSectionOption[];
  canDelete?: boolean;
  canManageAudiences?: boolean;
  pageTenants?: LearningTenantOption[];
  canPublishEverywhere?: boolean;
}>(), {
  item: null,
  audienceTypes: () => Object.values(LearningAudienceType).filter(type => type !== LearningAudienceType.Purpose),
  canTargetEverywhere: false,
  tenants: () => [], institutionTypes: () => [], dutyTypes: () => [], responsibilities: () => [], sections: () => [],
  canDelete: false,
  canManageAudiences: false,
  pageTenants: () => [],
  canPublishEverywhere: false,
});

// One instance per editor: each locale's editor is recreated with its own.
const wikiLinks = () => createWikiLinkExtension();
const linkSpotlight = useFeatureSpotlight('wiki-links-v1');
const SUMMARY_MAX = 200;

const activeLocale = ref<LocaleKey>('lt');
const isEdit = computed(() => Boolean(props.item?.id));
const deleteDialogOpen = ref(false);
const backHref = computed(() => props.item?.id ? route('knowledgeItems.show', props.item.id) : route('knowledgeItems.index'));

const source = (locale: LocaleKey) => ({ document_id: props.item?.sources?.[locale]?.document_id ?? null, url: props.item?.sources?.[locale]?.url ?? '' });

const form = useForm<KnowledgeItemFormState>({
  title: { lt: props.item?.title.lt ?? '', en: props.item?.title.en ?? '' },
  abbreviation: { lt: props.item?.abbreviation.lt ?? '', en: props.item?.abbreviation.en ?? '' },
  summary: { lt: props.item?.summary.lt ?? '', en: props.item?.summary.en ?? '' },
  description: { lt: props.item?.description.lt ?? '', en: props.item?.description.en ?? '' },
  sources: { lt: source('lt'), en: source('en') },
  audiences: props.item?.audiences.map(audience => ({ ...audience, description: { ...audience.description } })) ?? [],
  knowledge_section_id: props.item?.knowledge_section_id ?? null,
  tenant_id: props.item ? props.item.tenant_id : (!props.canPublishEverywhere && props.pageTenants.length === 1 ? props.pageTenants[0]!.id : null),
  order: props.item?.order ?? 0,
  is_active: props.item?.is_active ?? true,
});

const fieldIds: Record<string, string> = {
  'title': 'step-title', 'title.lt': 'step-title', 'title.en': 'step-title',
  'summary': 'article-summary', 'summary.lt': 'article-summary', 'summary.en': 'article-summary',
  'abbreviation.lt': 'article-abbreviation', 'abbreviation.en': 'article-abbreviation', 'tenant_id': 'article-tenant',
  'description': 'step-description', 'description.lt': 'step-description', 'description.en': 'step-description',
  'audiences': 'step-audiences', 'knowledge_section_id': 'step-section', 'order': 'step-order',
  'sources.lt': 'step-source', 'sources.lt.document_id': 'step-source', 'sources.lt.url': 'source-url-lt',
  'sources.en': 'step-source', 'sources.en.document_id': 'step-source', 'sources.en.url': 'source-url-en',
};

const missingLocaleCounts = computed<Record<LocaleKey, number>>(() => ({
  lt: (form.title.lt.trim() === '' ? 1 : 0) + (form.summary.lt.trim() === '' ? 1 : 0),
  en: (form.title.en.trim() === '' ? 1 : 0) + (form.summary.lt.trim() !== '' && form.summary.en.trim() === '' ? 1 : 0) + (hasRichText(form.description.lt) && !hasRichText(form.description.en) ? 1 : 0),
}));

const pageTitle = computed(() => {
  const title = form.title[activeLocale.value].trim() || form.title.lt.trim() || form.title.en.trim();
  return isEdit.value ? title || $t('learning.pages.knowledge_edit') : $t('learning.pages.knowledge_create');
});
const barTitle = computed(() => isEdit.value ? props.item?.title.lt || props.item?.title.en || $t('learning.pages.knowledge_edit') : $t('learning.pages.knowledge_create'));

const sectionOptions = computed(() => props.sections.map(section => ({ id: section.id, title: section.title[activeLocale.value] || section.title.lt || section.title.en || '' })));
const selectedSection = computed({
  get: () => sectionOptions.value.find(section => section.id === form.knowledge_section_id) ?? null,
  set: (section: { id: string; title: string } | null) => { form.knowledge_section_id = section?.id ?? null; },
});

function payload(data: KnowledgeItemFormState): Record<string, unknown> {
  const sources: Record<string, { document_id: number | null; url: string | null }> = {};
  for (const locale of ['lt', 'en'] as const) {
    const documentId = data.sources[locale].document_id;
    const url = documentId === null ? data.sources[locale].url.trim() || null : null;
    if (documentId !== null || url !== null) {
      sources[locale] = { document_id: documentId, url };
    }
  }

  return {
    title: data.title,
    abbreviation: data.abbreviation,
    summary: data.summary,
    description: data.description,
    sources,
    ...(props.canManageAudiences ? { audiences: data.audiences.map(({ label, stage, ...audience }) => audience) } : {}),
    knowledge_section_id: data.knowledge_section_id,
    tenant_id: data.tenant_id,
    order: data.order,
    is_active: data.is_active,
  };
}

function submit(): void {
  form.transform(payload);
  form.defaults();
  const options = { preserveScroll: true, onSuccess: () => form.defaults() };
  if (props.item?.id) {
    form.put(route('knowledgeItems.update', props.item.id), options);
  }
  else {
    form.post(route('knowledgeItems.store'), options);
  }
}

function destroy(): void {
  if (!props.item?.id) {
    return;
  }
  deleteDialogOpen.value = false;
  router.delete(route('knowledgeItems.destroy', props.item.id));
}
</script>
