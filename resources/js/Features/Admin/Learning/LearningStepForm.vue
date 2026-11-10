<template>
  <FormPage
    :title="pageTitle"
    :bar-title="barTitle"
    :entity-type="ModelEnum.LEARNING_STEP"
    required-note
    :back-href="backHref"
    :back-label="$t('learning.pages.library')"
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
    <template v-if="isSystem">
      <FormFieldWrapper
        id="system-title"
        :label="$t('learning_library.fields.title')"
        :hint="$t('learning_library.sections.system_description')"
      >
        <p data-testid="system-step-title" class="text-sm font-medium text-foreground">
          {{ systemTitle }}
        </p>
      </FormFieldWrapper>
    </template>

    <template v-else>
      <FormFieldWrapper
        id="step-title"
        :label="$t('learning_library.fields.title')"
        :hint="$t('learning_library.fields.title_hint')"
        :error="form.errors[`title.${activeLocale}`] || form.errors.title"
        :required="activeLocale === 'lt'"
      >
        <Input
          id="step-title"
          v-model="form.title[activeLocale]"
          :class="['h-11', fieldSurfaceClass]"
          data-testid="step-title"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="step-description"
        :label="$t('learning_library.fields.why_useful')"
        :hint="$t('learning_library.fields.why_useful_hint')"
        :error="form.errors[`description.${activeLocale}`] || form.errors.description"
      >
        <Textarea
          id="step-description"
          v-model="form.description[activeLocale]"
          rows="3"
          :class="fieldSurfaceClass"
          data-testid="step-description"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="step-kind"
        :label="$t('learning_library.fields.kind')"
        :hint="$t('learning_library.fields.kind_hint')"
        :error="form.errors.kind"
        required
      >
        <FormSegmentedControl
          v-model="form.kind"
          :options="kindOptions"
          :ariaLabel="$t('learning_library.fields.kind')"
          test-id-prefix="step-kind"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="step-source"
        :label="$t('learning_library.fields.source')"
        :hint="activeLocale === 'en' ? $t('learning_library.source.en_optional_hint') : $t('learning_library.fields.source_hint')"
        :error="form.errors[`sources.${activeLocale}`] || form.errors[`sources.${activeLocale}.document_id`] || form.errors[`sources.${activeLocale}.url`]"
        :required="activeLocale === 'lt' && form.kind === LearningStepKind.Knowledge"
      >
        <div class="space-y-3" :data-testid="`source-block-${activeLocale}`">
          <FormSegmentedControl
            :model-value="sourceModes[activeLocale]"
            :options="sourceModeOptions"
            :ariaLabel="$t('learning_library.fields.source')"
            :test-id-prefix="`source-mode-${activeLocale}`"
            @update:model-value="mode => setSourceMode(activeLocale, mode)"
          />

          <div v-if="sourceModes[activeLocale] === 'document'" class="space-y-2">
            <div
              v-if="selectedDocs[activeLocale]"
              class="flex items-center justify-between gap-2 border border-border bg-surface-subtle px-3 py-2"
              :data-testid="`selected-document-${activeLocale}`"
            >
              <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                <span class="truncate text-sm font-medium text-foreground">
                  {{ selectedDocs[activeLocale]!.title }}
                </span>
                <span v-if="selectedDocs[activeLocale]!.date" class="text-xs tabular-nums text-muted-foreground">
                  {{ selectedDocs[activeLocale]!.date }}
                </span>
                <StatusBadge
                  v-if="documentStatuses[selectedDocs[activeLocale]!.status]"
                  :status="documentStatuses[selectedDocs[activeLocale]!.status]"
                  voice="sentence"
                />
              </div>
              <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-11 shrink-0"
                :aria-label="$t('learning_library.source.clear')"
                :data-testid="`clear-document-${activeLocale}`"
                @click="clearDocument(activeLocale)"
              >
                <X class="size-4" aria-hidden="true" />
              </Button>
            </div>

            <div v-else class="relative">
              <div class="relative flex items-center">
                <Search class="pointer-events-none absolute left-3 size-4 text-muted-foreground" aria-hidden="true" />
                <Input
                  :id="`source-doc-search-${activeLocale}`"
                  v-model="searchQueries[activeLocale]"
                  type="text"
                  :placeholder="$t('learning_library.source.search_placeholder')"
                  :class="['pl-9', fieldSurfaceClass]"
                  :data-testid="`document-search-input-${activeLocale}`"
                  @focus="runDocumentSearch(activeLocale)"
                  @input="onSearchInput(activeLocale)"
                />
              </div>

              <div
                v-if="searchOpen[activeLocale] && (searching[activeLocale] || searchResults[activeLocale].length > 0 || searchQueries[activeLocale].trim().length > 0)"
                class="mt-1 max-h-60 overflow-y-auto border border-border bg-popover text-popover-foreground"
                role="listbox"
                :data-testid="`document-results-${activeLocale}`"
              >
                <p v-if="searching[activeLocale]" class="px-3 py-2 text-xs text-muted-foreground">
                  {{ $t('learning_library.source.searching') }}
                </p>
                <p v-else-if="searchResults[activeLocale].length === 0" class="px-3 py-2 text-xs text-muted-foreground">
                  {{ $t('learning_library.source.no_results') }}
                </p>
                <button
                  v-for="doc in searchResults[activeLocale]"
                  :key="doc.id"
                  type="button"
                  role="option"
                  class="flex min-h-11 w-full items-center justify-between gap-2 border-b border-border px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent"
                  :data-testid="`document-hit-${activeLocale}-${doc.id}`"
                  @click="selectDocument(activeLocale, doc)"
                >
                  <span class="min-w-0 flex-1 truncate font-medium">{{ doc.title }}</span>
                  <span class="flex shrink-0 items-center gap-2">
                    <span v-if="doc.date" class="text-xs tabular-nums text-muted-foreground">{{ doc.date }}</span>
                    <StatusBadge
                      v-if="documentStatuses[doc.status]"
                      :status="documentStatuses[doc.status]"
                      voice="sentence"
                    />
                  </span>
                </button>
              </div>
            </div>

            <p
              v-if="selectedDocs[activeLocale] && selectedDocs[activeLocale]!.status !== DocumentStatus.Published"
              :data-testid="`hidden-document-warning-${activeLocale}`"
              data-slot="hidden-document-warning"
              class="text-xs text-status-attention"
            >
              {{ $t('learning_library.source.hidden_document_warning') }}
            </p>
          </div>

          <div v-else class="space-y-1.5">
            <Input
              :id="`source-url-${activeLocale}`"
              v-model="form.sources[activeLocale].url"
              type="url"
              inputmode="url"
              :class="fieldSurfaceClass"
              :placeholder="$t('learning_library.source.url_placeholder')"
              :data-testid="`source-url-${activeLocale}`"
            />
          </div>
        </div>
      </FormFieldWrapper>
    </template>

    <section id="step-audiences" class="space-y-3" data-testid="step-audiences">
      <h2 class="text-lg font-semibold">{{ $t('learning_library.fields.audience') }}</h2>
      <p v-if="form.errors.audiences" class="text-sm text-destructive" role="alert">{{ form.errors.audiences }}</p>
      <ul class="divide-y divide-border border-y border-border">
        <li v-for="(audience, index) in form.audiences" :key="audience.id ?? index" class="flex flex-wrap items-start gap-2 py-3" data-slot="audience-row">
          <div class="min-w-0 flex-1 basis-52 space-y-1">
            <p class="break-words text-sm font-semibold">{{ audience.label }}</p>
            <p class="text-xs text-muted-foreground">
              {{ $t(`learning.importance.${audience.importance}`) }}
              <template v-if="audience.stage"> · {{ $t(`learning.stages.${audience.stage}`) }}</template>
            </p>
            <p v-if="audience.description[activeLocale]" class="line-clamp-2 text-sm text-muted-foreground">{{ audience.description[activeLocale] }}</p>
            <p v-for="(error, key) in audienceErrors(index)" :key="key" class="text-sm text-destructive" role="alert">{{ error }}</p>
          </div>
          <div class="flex gap-1">
            <Button type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Redaguoti')}: ${audience.label}`" @click="editAudience(index)">{{ $t('Redaguoti') }}</Button>
            <Button v-if="!isSystem" type="button" variant="ghost" voice="sentence" class="min-h-11" :aria-label="`${$t('Pašalinti')}: ${audience.label}`" @click="form.audiences.splice(index, 1)">{{ $t('Pašalinti') }}</Button>
          </div>
        </li>
      </ul>
      <Button v-if="!isSystem" type="button" variant="outline" voice="sentence" class="min-h-11" data-testid="add-audience" @click="editAudience(null)">
        <Plus class="size-4" aria-hidden="true" />{{ $t('learning_library.audiences.add') }}
      </Button>
    </section>

    <FormFieldWrapper v-if="!isSystem && form.kind === LearningStepKind.Knowledge" id="step-section" :label="$t('learning_library.fields.section')" :error="form.errors.learning_section_id">
      <SingleSelect v-if="sectionOptions.length > 16" v-model="selectedSection" :options="sectionOptions" label-field="title" value-field="id" :placeholder="$t('learning_library.audiences.no_section')" />
      <NativeSelect v-else id="step-section" v-model="form.learning_section_id" :placeholder-value="null" placeholder-selectable :options="sectionOptions.map(section => ({ value: section.id, label: section.title }))" :placeholder="$t('learning_library.audiences.no_section')" data-testid="step-section" />
    </FormFieldWrapper>

    <template #aside>
      <FormPanel
        :title="$t('learning_library.sections.additional')"
        :icon="SlidersHorizontal"
        data-testid="panel-additional"
      >
        <FormFieldWrapper
          id="step-order"
          :label="$t('learning_library.fields.order')"
          :error="form.errors.order"
        >
          <Input
            id="step-order"
            v-model.number="form.order"
            type="number"
            min="0"
            :class="['max-w-32', fieldSurfaceClass]"
            data-testid="step-order"
          />
        </FormFieldWrapper>

        <div v-if="!isSystem" class="flex items-start gap-2.5">
          <Checkbox id="step-per-term" v-model="form.per_term" data-testid="step-per-term" />
          <div class="space-y-0.5 leading-none">
            <Label for="step-per-term" class="text-sm font-medium">
              {{ $t('learning_library.fields.per_term') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('learning_library.fields.per_term_hint') }}
            </p>
          </div>
        </div>

        <div class="flex items-start gap-2.5">
          <Checkbox id="step-is-active" v-model="form.is_active" data-testid="step-is-active" />
          <div class="space-y-0.5 leading-none">
            <Label for="step-is-active" class="text-sm font-medium">
              {{ $t('learning_library.fields.is_active') }}
            </Label>
            <p class="text-xs text-muted-foreground">
              {{ $t('learning_library.fields.is_active_hint') }}
            </p>
          </div>
        </div>
      </FormPanel>
    </template>

    <template v-if="isEdit && !isSystem && canDelete" #danger-zone>
      <Button
        type="button"
        variant="destructive"
        voice="sentence"
        data-testid="delete-learning-step"
        @click="deleteDialogOpen = true"
      >
        <Trash2 class="size-4" aria-hidden="true" />
        {{ $t('learning_library.delete.action') }}
      </Button>
    </template>
  </FormPage>

  <LearningAudienceSheet
    v-model:open="audienceSheetOpen"
    :audience="audienceIndex === null ? null : form.audiences[audienceIndex]"
    :kind="form.kind"
    :is-system="isSystem"
    :audience-types="audienceTypes"
    :can-target-everywhere="canTargetEverywhere"
    :tenants="tenants"
    :institution-types="institutionTypes"
    :duty-types="dutyTypes"
    :responsibilities="responsibilities"
    @save="saveAudience"
  />

  <ConfirmDialog
    v-if="isEdit && !isSystem && canDelete"
    v-model:open="deleteDialogOpen"
    :title="$t('learning_library.delete.title')"
    :description="$t('learning_library.delete.description')"
    :confirm-label="$t('learning_library.delete.confirm')"
    destructive
    @confirm="destroyStep"
  />
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Search, SlidersHorizontal, Trash2, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel, FormSegmentedControl, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { Textarea } from '@/Components/ui/textarea';
import { documentStatuses } from '@/Constants/statusIcons';
import {
  DocumentStatus,
  LearningAudienceType,
  LearningStage,
  LearningStepKind,
  ModelEnum,
} from '@/Types/enums';
import type {
  LearningDocumentSearchHit,
  LearningStepEditData,
  LearningTenantOption,
  LearningTypeOption,
} from '@/Types/learning';

import LearningAudienceSheet from './LearningAudienceSheet.vue';
import type { LearningAudienceDraft, LearningSectionOption } from './types';

type LocaleKey = 'lt' | 'en';
type SourceMode = 'document' | 'url';

interface LearningStepFormState {
  kind: LearningStepKind;
  audiences: LearningAudienceDraft[];
  learning_section_id: string | null;
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  per_term: boolean;
  order: number;
  is_active: boolean;
  sources: Record<LocaleKey, { document_id: number | null; url: string }>;
}

const props = withDefaults(defineProps<{
  step?: LearningStepEditData | null;
  audienceTypes?: LearningAudienceType[];
  canTargetEverywhere?: boolean;
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  responsibilities?: { id: string; title: string }[];
  sections?: LearningSectionOption[];
  canDelete?: boolean;
}>(), {
  step: null,
  audienceTypes: () => Object.values(LearningAudienceType).filter(type => type !== LearningAudienceType.Purpose),
  canTargetEverywhere: false,
  tenants: () => [], institutionTypes: () => [], dutyTypes: () => [], responsibilities: () => [], sections: () => [],
  canDelete: false,
});

const activeLocale = ref<LocaleKey>('lt');
const isEdit = computed(() => Boolean(props.step?.id));
const isSystem = computed(() => Boolean(props.step?.is_system));
const backHref = computed(() => (props.step?.id ? route('learningSteps.show', props.step.id) : route('learningSteps.index')));

const form = useForm<LearningStepFormState>({
  kind: props.step?.kind ?? LearningStepKind.Knowledge,
  audiences: props.step?.audiences.map(audience => ({ ...audience, description: { ...audience.description } })) ?? [],
  learning_section_id: props.step?.learning_section_id ?? null,
  title: {
    lt: props.step?.title?.lt ?? '',
    en: props.step?.title?.en ?? '',
  },
  description: {
    lt: props.step?.description?.lt ?? '',
    en: props.step?.description?.en ?? '',
  },
  per_term: props.step?.per_term ?? false,
  order: props.step?.order ?? 0,
  is_active: props.step?.is_active ?? true,
  sources: {
    lt: {
      document_id: props.step?.sources?.lt?.document_id ?? null,
      url: props.step?.sources?.lt?.url ?? '',
    },
    en: {
      document_id: props.step?.sources?.en?.document_id ?? null,
      url: props.step?.sources?.en?.url ?? '',
    },
  },
});

const fieldIds: Record<string, string> = {
  'title': 'step-title',
  'title.lt': 'step-title',
  'title.en': 'step-title',
  'description': 'step-description',
  'description.lt': 'step-description',
  'description.en': 'step-description',
  'kind': 'step-kind',
  'audiences': 'step-audiences',
  'learning_section_id': 'step-section',
  'order': 'step-order',
  'sources.lt': 'step-source',
  'sources.lt.document_id': 'step-source',
  'sources.lt.url': 'source-url-lt',
  'sources.en': 'step-source',
  'sources.en.document_id': 'step-source',
  'sources.en.url': 'source-url-en',
};

const missingLocaleCounts = computed<Record<LocaleKey, number>>(() => {
  if (isSystem.value) {
    return { lt: 0, en: 0 };
  }
  return {
    lt: form.title.lt.trim() === '' ? 1 : 0,
    en: (form.title.en.trim() === '' ? 1 : 0)
      + (form.description.lt.trim() !== '' && form.description.en.trim() === '' ? 1 : 0),
  };
});

const sourceModes = reactive<Record<LocaleKey, SourceMode>>({
  lt: props.step?.sources?.lt?.url && !props.step?.sources?.lt?.document_id ? 'url' : 'document',
  en: props.step?.sources?.en?.url && !props.step?.sources?.en?.document_id ? 'url' : 'document',
});

const selectedDocs = reactive<Record<LocaleKey, LearningDocumentSearchHit | null>>({
  lt: props.step?.source_documents?.lt ?? null,
  en: props.step?.source_documents?.en ?? null,
});

const searchQueries = reactive<Record<LocaleKey, string>>({ lt: '', en: '' });
const searchResults = reactive<Record<LocaleKey, LearningDocumentSearchHit[]>>({ lt: [], en: [] });
const searching = reactive<Record<LocaleKey, boolean>>({ lt: false, en: false });
const searchOpen = reactive<Record<LocaleKey, boolean>>({ lt: false, en: false });
const deleteDialogOpen = ref(false);

const systemTitle = computed(() => {
  if (props.step?.key) {
    return $t(`learning.system_steps.${props.step.key}.title`);
  }
  return props.step?.title?.[activeLocale.value] || props.step?.title?.lt || props.step?.title?.en || '';
});

const pageTitle = computed(() => {
  if (isSystem.value) {
    return systemTitle.value || $t('learning.pages.edit');
  }
  const activeTitle = form.title[activeLocale.value].trim() || form.title.lt.trim() || form.title.en.trim();
  if (isEdit.value) {
    return activeTitle || $t('learning.pages.edit');
  }
  return $t('learning.pages.create');
});

const barTitle = computed(() => {
  if (isSystem.value) {
    return systemTitle.value || $t('learning.pages.edit');
  }
  if (isEdit.value) {
    return props.step?.title?.lt || props.step?.title?.en || $t('learning.pages.edit');
  }
  return $t('learning.pages.create');
});

const kindOptions = computed<FormSegmentOption<LearningStepKind>[]>(() => [
  { value: LearningStepKind.Knowledge, label: $t('learning.kinds.knowledge') },
  { value: LearningStepKind.Action, label: $t('learning.kinds.action') },
  { value: LearningStepKind.MeetingPrep, label: $t('learning.kinds.meeting_prep') },
]);

const sourceModeOptions = computed<FormSegmentOption<SourceMode>[]>(() => [
  { value: 'document', label: $t('learning_library.source.mode_document') },
  { value: 'url', label: $t('learning_library.source.mode_url') },
]);

const sectionOptions = computed(() => props.sections.map(section => ({ id: section.id, title: section.title[activeLocale.value] || section.title.lt || section.title.en || '' })));
const selectedSection = computed({
  get: () => sectionOptions.value.find(section => section.id === form.learning_section_id) ?? null,
  set: (section: { id: string; title: string } | null) => { form.learning_section_id = section?.id ?? null; },
});
const audienceSheetOpen = ref(false);
const audienceIndex = ref<number | null>(null);
function editAudience(index: number | null) {
  audienceIndex.value = index;
  audienceSheetOpen.value = true;
}
function saveAudience(audience: LearningAudienceDraft) {
  if (audienceIndex.value === null) form.audiences.push({ ...audience, order: Math.max(-1, ...form.audiences.map(row => row.order)) + 1 });
  else form.audiences[audienceIndex.value] = audience;
}
function audienceErrors(index: number) {
  return Object.fromEntries(Object.entries(form.errors).filter(([key]) => key === `audiences.${index}` || key.startsWith(`audiences.${index}.`)));
}
watch(() => form.kind, kind => {
  for (const audience of form.audiences) {
    audience.stage = kind === LearningStepKind.MeetingPrep ? null : audience.stage ?? LearningStage.BeforeStart;
  }
  if (kind !== LearningStepKind.Knowledge) form.learning_section_id = null;
});

function setSourceMode(locale: LocaleKey, mode: SourceMode): void {
  sourceModes[locale] = mode;
  if (mode === 'url') {
    form.sources[locale].document_id = null;
    selectedDocs[locale] = null;
  }
  else {
    form.sources[locale].url = '';
  }
}

function selectDocument(locale: LocaleKey, doc: LearningDocumentSearchHit): void {
  selectedDocs[locale] = doc;
  form.sources[locale].document_id = doc.id;
  form.sources[locale].url = '';
  searchOpen[locale] = false;
}

function clearDocument(locale: LocaleKey): void {
  selectedDocs[locale] = null;
  form.sources[locale].document_id = null;
}

const searchTimers: Record<LocaleKey, ReturnType<typeof setTimeout> | null> = { lt: null, en: null };

function onSearchInput(locale: LocaleKey): void {
  if (searchTimers[locale]) {
    clearTimeout(searchTimers[locale]!);
  }
  searchTimers[locale] = setTimeout(() => {
    void runDocumentSearch(locale);
  }, 150);
}

async function runDocumentSearch(locale: LocaleKey): Promise<void> {
  searchOpen[locale] = true;
  searching[locale] = true;
  try {
    const response = await fetch(route('learningSteps.documentSearch', { q: searchQueries[locale].trim() }), {
      headers: { Accept: 'application/json' },
    });
    if (response.ok) {
      const data = await response.json();
      searchResults[locale] = Array.isArray(data) ? data : (Array.isArray(data?.data) ? data.data : []);
    }
  }
  finally {
    searching[locale] = false;
  }
}

function buildPayload(data: LearningStepFormState): Record<string, unknown> {
  if (isSystem.value) {
    return {
      audiences: data.audiences.map(({ id, stage, order }) => ({ id, stage, order })),
      order: data.order,
      is_active: data.is_active,
    };
  }

  const sources: Record<string, { document_id: number | null; url: string | null }> = {};
  for (const locale of ['lt', 'en'] as const) {
    const source = data.sources[locale];
    const docId = sourceModes[locale] === 'document' ? source.document_id : null;
    const url = sourceModes[locale] === 'url' ? source.url.trim() || null : null;
    if (docId !== null || url !== null) {
      sources[locale] = { document_id: docId, url };
    }
  }

  return {
    kind: data.kind,
    audiences: data.audiences.map(({ label, ...audience }) => ({
      ...audience, stage: data.kind === LearningStepKind.MeetingPrep ? null : audience.stage,
    })),
    learning_section_id: data.kind === LearningStepKind.Knowledge ? data.learning_section_id : null,
    title: data.title,
    description: data.description,
    per_term: data.per_term,
    order: data.order,
    is_active: data.is_active,
    sources,
  };
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
    },
  };

  form.transform(data => buildPayload(data));
  form.defaults();

  if (props.step?.id) {
    form.put(route('learningSteps.update', props.step.id), options);
  }
  else {
    form.post(route('learningSteps.store'), options);
  }
}

function destroyStep(): void {
  if (!props.step?.id) {
    return;
  }
  deleteDialogOpen.value = false;
  router.delete(route('learningSteps.destroy', props.step.id));
}
</script>
