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

    <LearningSourceField v-model="form.sources" :locale="activeLocale" :errors="form.errors" :documents="item?.source_documents" />

    <FormFieldWrapper
      id="step-description"
      :label="$t('learning_library.fields.description')"
      :hint="$t('learning_library.fields.knowledge_description_hint')"
      :error="form.errors[`description.${activeLocale}`] || form.errors.description"
    >
      <TiptapEditor :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html data-testid="step-description" />
    </FormFieldWrapper>

    <LearningAudienceList
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
        <FormFieldWrapper id="step-section" :label="$t('learning_library.fields.section')" :hint="$t('learning_library.fields.section_hint')" :error="form.errors.knowledge_section_id">
          <SingleSelect v-if="sectionOptions.length > 16" v-model="selectedSection" :options="sectionOptions" label-field="title" value-field="id" :placeholder="$t('learning_library.audiences.no_section')" />
          <NativeSelect v-else id="step-section" v-model="form.knowledge_section_id" :placeholder-value="null" placeholder-selectable :options="sectionOptions.map(section => ({ value: section.id, label: section.title }))" :placeholder="$t('learning_library.audiences.no_section')" data-testid="step-section" />
        </FormFieldWrapper>

        <FormFieldWrapper id="step-order" :label="$t('learning_library.fields.order')" :hint="$t('learning_library.fields.knowledge_order_hint')" :error="form.errors.order">
          <Input id="step-order" v-model.number="form.order" type="number" min="0" :class="['max-w-32', fieldSurfaceClass]" data-testid="step-order" />
        </FormFieldWrapper>
      </FormPanel>
      <FormPanel :title="$t('learning_library.sections.visibility')" :icon="Eye" flush>
        <FormToggleRow id="step-per-term" v-model="form.per_term" :label="$t('learning_library.fields.knowledge_per_term')" :hint="$t('learning_library.fields.knowledge_per_term_hint')" data-testid="step-per-term" />
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
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
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
  description: { lt: string; en: string };
  sources: LearningSourceDraft;
  audiences: LearningAudienceDraft[];
  knowledge_section_id: string | null;
  per_term: boolean;
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
}>(), {
  item: null,
  audienceTypes: () => Object.values(LearningAudienceType).filter(type => type !== LearningAudienceType.Purpose),
  canTargetEverywhere: false,
  tenants: () => [], institutionTypes: () => [], dutyTypes: () => [], responsibilities: () => [], sections: () => [],
  canDelete: false,
});

const activeLocale = ref<LocaleKey>('lt');
const isEdit = computed(() => Boolean(props.item?.id));
const deleteDialogOpen = ref(false);
const backHref = computed(() => props.item?.id ? route('knowledgeItems.show', props.item.id) : route('knowledgeItems.index'));

const source = (locale: LocaleKey) => ({ document_id: props.item?.sources?.[locale]?.document_id ?? null, url: props.item?.sources?.[locale]?.url ?? '' });

const form = useForm<KnowledgeItemFormState>({
  title: { lt: props.item?.title.lt ?? '', en: props.item?.title.en ?? '' },
  description: { lt: props.item?.description.lt ?? '', en: props.item?.description.en ?? '' },
  sources: { lt: source('lt'), en: source('en') },
  audiences: props.item?.audiences.map(audience => ({ ...audience, description: { ...audience.description } })) ?? [],
  knowledge_section_id: props.item?.knowledge_section_id ?? null,
  per_term: props.item?.per_term ?? false,
  order: props.item?.order ?? 0,
  is_active: props.item?.is_active ?? true,
});

const fieldIds: Record<string, string> = {
  'title': 'step-title', 'title.lt': 'step-title', 'title.en': 'step-title',
  'description': 'step-description', 'description.lt': 'step-description', 'description.en': 'step-description',
  'audiences': 'step-audiences', 'knowledge_section_id': 'step-section', 'order': 'step-order',
  'sources.lt': 'step-source', 'sources.lt.document_id': 'step-source', 'sources.lt.url': 'source-url-lt',
  'sources.en': 'step-source', 'sources.en.document_id': 'step-source', 'sources.en.url': 'source-url-en',
};

const missingLocaleCounts = computed<Record<LocaleKey, number>>(() => ({
  lt: form.title.lt.trim() === '' ? 1 : 0,
  en: (form.title.en.trim() === '' ? 1 : 0) + (hasRichText(form.description.lt) && !hasRichText(form.description.en) ? 1 : 0),
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
    description: data.description,
    sources,
    audiences: data.audiences.map(({ label, stage, ...audience }) => audience),
    knowledge_section_id: data.knowledge_section_id,
    per_term: data.per_term,
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
