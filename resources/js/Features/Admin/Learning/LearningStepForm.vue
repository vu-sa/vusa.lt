<template>
  <FormPage
    :eyebrow="$t('learning.tabs.library')"
    :title="isEdit ? $t('learning.pages.edit') : $t('learning.pages.create')"
    :back-href="backHref"
    :dirty="form.isDirty"
    :processing="form.processing"
    :errors="form.errors"
    max-width="3xl"
    @submit="submit"
    @cancel="cancel"
  >
    <template v-if="isSystem">
      <FormSection
        :title="$t('learning_library.sections.when')"
        :description="$t('learning_library.sections.system_description')"
      >
        <FormFieldWrapper id="system-title" :label="$t('learning_library.fields.title')">
          <p data-testid="system-step-title" class="text-sm font-medium text-foreground">
            {{ systemTitle }}
          </p>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="step-stage"
          :label="$t('learning_library.fields.stage')"
          :error="form.errors.stage"
          required
        >
          <FormSegmentedControl
            v-model="stageModel"
            :options="stageOptions"
            :aria-label="$t('learning_library.fields.stage')"
            test-id-prefix="step-stage"
          />
        </FormFieldWrapper>

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
            class="max-w-32"
            data-testid="step-order"
          />
        </FormFieldWrapper>

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
      </FormSection>
    </template>

    <template v-else>
      <FormSection
        :title="$t('learning_library.sections.what')"
        :description="$t('learning_library.sections.what_description')"
      >
        <FormFieldWrapper
          id="step-kind"
          :label="$t('learning_library.fields.kind')"
          :error="form.errors.kind"
          required
        >
          <FormSegmentedControl
            v-model="form.kind"
            :options="kindOptions"
            :aria-label="$t('learning_library.fields.kind')"
            test-id-prefix="step-kind"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="step-importance"
          :label="$t('learning_library.fields.importance')"
          :error="form.errors.importance"
          required
        >
          <FormSegmentedControl
            v-model="form.importance"
            :options="importanceOptions"
            :aria-label="$t('learning_library.fields.importance')"
            test-id-prefix="step-importance"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="step-title"
          :label="$t('learning_library.fields.title')"
          :error="form.errors['title.lt'] || form.errors['title.en'] || form.errors.title"
          required
        >
          <MultiLocaleInput id="step-title" v-model:input="form.title" />
        </FormFieldWrapper>

        <FormFieldWrapper
          id="step-description"
          :label="$t('learning_library.fields.why_useful')"
          :hint="$t('learning_library.fields.why_useful_hint')"
          :error="form.errors['description.lt'] || form.errors['description.en'] || form.errors.description"
        >
          <MultiLocaleInput
            id="step-description"
            v-model:input="form.description"
            input-type="textarea"
          />
        </FormFieldWrapper>
      </FormSection>

      <FormSection
        :title="$t('learning_library.sections.who')"
        :description="$t('learning_library.sections.who_description')"
      >
        <FormFieldWrapper
          id="step-layer"
          :label="$t('learning_library.fields.layer')"
          :error="form.errors.layer"
          required
        >
          <FormSegmentedControl
            v-model="form.layer"
            :options="layerOptions"
            :aria-label="$t('learning_library.fields.layer')"
            test-id-prefix="step-layer"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="form.layer === LearningLayer.InstitutionType"
          id="step-institution-type"
          :label="$t('learning_library.fields.institution_type')"
          :error="form.errors.institution_type_id"
          required
        >
          <SingleSelect
            v-if="institutionTypes.length > 16"
            v-model="selectedInstitutionType"
            :options="institutionTypes"
            label-field="title"
            value-field="id"
            :placeholder="$t('learning_library.fields.institution_type_placeholder')"
          />
          <NativeSelect
            v-else
            id="step-institution-type"
            v-model="institutionTypeIdString"
            :options="institutionTypes.map(type => ({ value: String(type.id), label: type.title }))"
            :placeholder="$t('learning_library.fields.institution_type_placeholder')"
            data-testid="step-institution-type"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-else-if="form.layer === LearningLayer.DutyType"
          id="step-duty-type"
          :label="$t('learning_library.fields.duty_type')"
          :error="form.errors.duty_type_id"
          required
        >
          <SingleSelect
            v-if="dutyTypes.length > 16"
            v-model="selectedDutyType"
            :options="dutyTypes"
            label-field="title"
            value-field="id"
            :placeholder="$t('learning_library.fields.duty_type_placeholder')"
          />
          <NativeSelect
            v-else
            id="step-duty-type"
            v-model="dutyTypeIdString"
            :options="dutyTypes.map(type => ({ value: String(type.id), label: type.title }))"
            :placeholder="$t('learning_library.fields.duty_type_placeholder')"
            data-testid="step-duty-type"
          />
        </FormFieldWrapper>

        <TenantSelectField
          v-else-if="form.layer === LearningLayer.Tenant"
          id="step-tenant"
          v-model="form.tenant_id"
          :tenants="tenants"
          :label="$t('learning_library.fields.tenant')"
          :error="form.errors.tenant_id"
          required
        />
      </FormSection>

      <FormSection
        :title="$t('learning_library.sections.when')"
        :description="$t('learning_library.sections.when_description')"
      >
        <FormFieldWrapper
          v-if="form.kind !== LearningStepKind.MeetingPrep"
          id="step-stage"
          :label="$t('learning_library.fields.stage')"
          :hint="form.layer === LearningLayer.Tenant ? $t('learning_library.fields.stage_fixed_hint') : undefined"
          :error="form.errors.stage"
          required
        >
          <p
            v-if="form.layer === LearningLayer.Tenant"
            data-testid="stage-fixed-tenant"
            class="text-sm font-medium text-foreground"
          >
            {{ $t('learning_library.fields.stage_fixed_before_start') }}
          </p>
          <FormSegmentedControl
            v-else
            v-model="stageModel"
            :options="stageOptions"
            :aria-label="$t('learning_library.fields.stage')"
            test-id-prefix="step-stage"
          />
        </FormFieldWrapper>

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
            class="max-w-32"
            data-testid="step-order"
          />
        </FormFieldWrapper>

        <div class="space-y-3">
          <div class="flex items-start gap-2.5">
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
        </div>
      </FormSection>

      <FormSection
        :title="$t('learning_library.sections.source')"
        :description="$t('learning_library.sections.source_description')"
      >
        <div
          v-for="locale in (['lt', 'en'] as const)"
          :key="locale"
          class="space-y-3 border-b border-border pb-4 last:border-b-0 last:pb-0"
          :data-testid="`source-block-${locale}`"
        >
          <FormFieldWrapper
            :id="`source-${locale}`"
            :label="$t(`learning_library.source.locale_${locale}`)"
            :hint="locale === 'en' ? $t('learning_library.source.en_optional_hint') : undefined"
            :error="form.errors[`sources.${locale}`] || form.errors[`sources.${locale}.document_id`] || form.errors[`sources.${locale}.url`]"
            :required="locale === 'lt' && form.kind === LearningStepKind.Knowledge"
          >
            <div class="space-y-3">
              <FormSegmentedControl
                :model-value="sourceModes[locale]"
                :options="sourceModeOptions"
                :aria-label="$t(`learning_library.source.locale_${locale}`)"
                :test-id-prefix="`source-mode-${locale}`"
                @update:model-value="mode => setSourceMode(locale, mode)"
              />

              <div v-if="sourceModes[locale] === 'document'" class="space-y-2">
                <div
                  v-if="selectedDocs[locale]"
                  class="flex items-center justify-between gap-2 border border-border bg-surface-subtle px-3 py-2"
                  :data-testid="`selected-document-${locale}`"
                >
                  <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
                    <span class="truncate text-sm font-medium text-foreground">
                      {{ selectedDocs[locale]!.title }}
                    </span>
                    <span v-if="selectedDocs[locale]!.date" class="text-xs tabular-nums text-muted-foreground">
                      {{ selectedDocs[locale]!.date }}
                    </span>
                    <StatusBadge
                      v-if="documentStatuses[selectedDocs[locale]!.status]"
                      :status="documentStatuses[selectedDocs[locale]!.status]"
                      voice="sentence"
                    />
                  </div>
                  <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    class="size-8 shrink-0"
                    :aria-label="$t('learning_library.source.clear')"
                    :data-testid="`clear-document-${locale}`"
                    @click="clearDocument(locale)"
                  >
                    <X class="size-4" aria-hidden="true" />
                  </Button>
                </div>

                <div v-else class="relative">
                  <div class="relative flex items-center">
                    <Search class="pointer-events-none absolute left-3 size-4 text-muted-foreground" aria-hidden="true" />
                    <Input
                      :id="`source-doc-search-${locale}`"
                      v-model="searchQueries[locale]"
                      type="text"
                      :placeholder="$t('learning_library.source.search_placeholder')"
                      class="pl-9"
                      :data-testid="`document-search-input-${locale}`"
                      @focus="runDocumentSearch(locale)"
                      @input="onSearchInput(locale)"
                    />
                  </div>

                  <div
                    v-if="searchOpen[locale] && (searching[locale] || searchResults[locale].length > 0 || searchQueries[locale].trim().length > 0)"
                    class="mt-1 max-h-60 overflow-y-auto border border-border bg-popover text-popover-foreground"
                    role="listbox"
                    :data-testid="`document-results-${locale}`"
                  >
                    <p v-if="searching[locale]" class="px-3 py-2 text-xs text-muted-foreground">
                      {{ $t('learning_library.source.searching') }}
                    </p>
                    <p v-else-if="searchResults[locale].length === 0" class="px-3 py-2 text-xs text-muted-foreground">
                      {{ $t('learning_library.source.no_results') }}
                    </p>
                    <button
                      v-for="doc in searchResults[locale]"
                      :key="doc.id"
                      type="button"
                      role="option"
                      class="flex w-full items-center justify-between gap-2 border-b border-border px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent"
                      :data-testid="`document-hit-${locale}-${doc.id}`"
                      @click="selectDocument(locale, doc)"
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
                  v-if="selectedDocs[locale] && selectedDocs[locale]!.status !== DocumentStatus.Published"
                  :data-testid="`hidden-document-warning-${locale}`"
                  data-slot="hidden-document-warning"
                  class="text-xs text-status-attention"
                >
                  {{ $t('learning_library.source.hidden_document_warning') }}
                </p>
              </div>

              <div v-else class="space-y-1.5">
                <Input
                  :id="`source-url-${locale}`"
                  v-model="form.sources[locale].url"
                  type="url"
                  inputmode="url"
                  :placeholder="$t('learning_library.source.url_placeholder')"
                  :data-testid="`source-url-${locale}`"
                />
              </div>
            </div>
          </FormFieldWrapper>
        </div>
      </FormSection>
    </template>

    <template v-if="isEdit && !isSystem && canDelete" #danger>
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
import { Search, Trash2, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormSection, FormSegmentedControl, StatusBadge, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import { documentStatuses } from '@/Constants/statusIcons';
import { DocumentStatus, LearningImportance, LearningLayer, LearningStage, LearningStepKind } from '@/Types/enums';
import type {
  LearningDocumentSearchHit,
  LearningStepEditData,
  LearningTenantOption,
  LearningTypeOption,
} from '@/Types/learning';

type LocaleKey = 'lt' | 'en';
type SourceMode = 'document' | 'url';

interface LearningStepFormState {
  kind: LearningStepKind;
  layer: LearningLayer;
  stage: LearningStage | null;
  importance: LearningImportance;
  title: { lt: string; en: string };
  description: { lt: string; en: string };
  institution_type_id: number | null;
  duty_type_id: number | null;
  tenant_id: number | null;
  per_term: boolean;
  order: number;
  is_active: boolean;
  sources: Record<LocaleKey, { document_id: number | null; url: string }>;
}

const ALL_LAYERS: LearningLayer[] = [
  LearningLayer.Platform,
  LearningLayer.Foundation,
  LearningLayer.InstitutionType,
  LearningLayer.DutyType,
  LearningLayer.Tenant,
];

const props = withDefaults(defineProps<{
  step?: LearningStepEditData | null;
  layers?: LearningLayer[];
  tenants?: LearningTenantOption[];
  institutionTypes?: LearningTypeOption[];
  dutyTypes?: LearningTypeOption[];
  canDelete?: boolean;
}>(), {
  step: null,
  layers: () => [
    LearningLayer.Platform,
    LearningLayer.Foundation,
    LearningLayer.InstitutionType,
    LearningLayer.DutyType,
    LearningLayer.Tenant,
  ],
  tenants: () => [],
  institutionTypes: () => [],
  dutyTypes: () => [],
  canDelete: false,
});

const isEdit = computed(() => Boolean(props.step?.id));
const isSystem = computed(() => Boolean(props.step?.is_system));
const backHref = computed(() => (props.step?.id ? route('learningSteps.show', props.step.id) : route('learningSteps.index')));

const allowedLayers = computed<LearningLayer[]>(() => (props.layers.length ? props.layers : ALL_LAYERS));
const initialLayer = computed<LearningLayer>(() => {
  if (props.step?.layer && allowedLayers.value.includes(props.step.layer)) {
    return props.step.layer;
  }
  return allowedLayers.value[0] ?? LearningLayer.Foundation;
});

const form = useForm<LearningStepFormState>({
  kind: props.step?.kind ?? LearningStepKind.Knowledge,
  layer: initialLayer.value,
  stage: props.step ? props.step.stage : LearningStage.BeforeStart,
  importance: props.step?.importance ?? LearningImportance.Essential,
  title: {
    lt: props.step?.title?.lt ?? '',
    en: props.step?.title?.en ?? '',
  },
  description: {
    lt: props.step?.description?.lt ?? '',
    en: props.step?.description?.en ?? '',
  },
  institution_type_id: props.step?.institution_type_id ?? null,
  duty_type_id: props.step?.duty_type_id ?? null,
  tenant_id: props.step?.tenant_id ?? (initialLayer.value === LearningLayer.Tenant && props.tenants.length === 1 ? props.tenants[0]!.id : null),
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
  return props.step?.title?.lt || props.step?.title?.en || '';
});

const kindOptions = computed<FormSegmentOption<LearningStepKind>[]>(() => [
  { value: LearningStepKind.Knowledge, label: $t('learning.kinds.knowledge') },
  { value: LearningStepKind.Action, label: $t('learning.kinds.action') },
  { value: LearningStepKind.MeetingPrep, label: $t('learning.kinds.meeting_prep') },
]);

const importanceOptions = computed<FormSegmentOption<LearningImportance>[]>(() => [
  { value: LearningImportance.Essential, label: $t('learning.importance.essential') },
  { value: LearningImportance.Recommended, label: $t('learning.importance.recommended') },
  { value: LearningImportance.Optional, label: $t('learning.importance.optional') },
]);

const layerOptions = computed<FormSegmentOption<LearningLayer>[]>(() =>
  allowedLayers.value.map(layer => ({
    value: layer,
    label: $t(`learning.layers.${layer}`),
  })));

const stageOptions = computed<FormSegmentOption<LearningStage>[]>(() => [
  { value: LearningStage.BeforeStart, label: $t('learning.stages.before_start') },
  { value: LearningStage.FirstMonths, label: $t('learning.stages.first_months') },
  { value: LearningStage.WholeTerm, label: $t('learning.stages.whole_term') },
  { value: LearningStage.TermEnd, label: $t('learning.stages.term_end') },
]);

const sourceModeOptions = computed<FormSegmentOption<SourceMode>[]>(() => [
  { value: 'document', label: $t('learning_library.source.mode_document') },
  { value: 'url', label: $t('learning_library.source.mode_url') },
]);

const stageModel = computed<LearningStage>({
  get: () => form.stage ?? LearningStage.BeforeStart,
  set: (value: LearningStage) => {
    form.stage = value;
  },
});

const institutionTypeIdString = computed({
  get: () => (form.institution_type_id !== null ? String(form.institution_type_id) : ''),
  set: (value: string) => {
    form.institution_type_id = value ? Number(value) : null;
  },
});

const dutyTypeIdString = computed({
  get: () => (form.duty_type_id !== null ? String(form.duty_type_id) : ''),
  set: (value: string) => {
    form.duty_type_id = value ? Number(value) : null;
  },
});

const selectedInstitutionType = computed({
  get: () => props.institutionTypes.find(type => type.id === form.institution_type_id) ?? null,
  set: (type: LearningTypeOption | null) => {
    form.institution_type_id = type?.id ?? null;
  },
});

const selectedDutyType = computed({
  get: () => props.dutyTypes.find(type => type.id === form.duty_type_id) ?? null,
  set: (type: LearningTypeOption | null) => {
    form.duty_type_id = type?.id ?? null;
  },
});

watch(() => form.kind, (kind) => {
  if (kind === LearningStepKind.MeetingPrep) {
    form.stage = null;
    if (allowedLayers.value.includes(LearningLayer.InstitutionType)) {
      form.layer = LearningLayer.InstitutionType;
    }
  }
  else if (form.stage === null) {
    form.stage = LearningStage.BeforeStart;
  }
});

watch(() => form.layer, (layer) => {
  if (layer !== LearningLayer.InstitutionType) {
    form.institution_type_id = null;
  }
  if (layer !== LearningLayer.DutyType) {
    form.duty_type_id = null;
  }
  if (layer !== LearningLayer.Tenant) {
    form.tenant_id = null;
  }
  if (layer === LearningLayer.Tenant) {
    form.stage = LearningStage.BeforeStart;
    if (props.tenants.length === 1 && form.tenant_id === null) {
      form.tenant_id = props.tenants[0]!.id;
    }
  }
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

let searchTimers: Record<LocaleKey, ReturnType<typeof setTimeout> | null> = { lt: null, en: null };

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
      stage: data.stage,
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
    layer: data.layer,
    stage: data.kind === LearningStepKind.MeetingPrep ? null : (data.layer === LearningLayer.Tenant ? LearningStage.BeforeStart : data.stage),
    importance: data.importance,
    title: data.title,
    description: data.description,
    institution_type_id: data.layer === LearningLayer.InstitutionType ? data.institution_type_id : null,
    duty_type_id: data.layer === LearningLayer.DutyType ? data.duty_type_id : null,
    tenant_id: data.layer === LearningLayer.Tenant ? data.tenant_id : null,
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

  if (props.step?.id) {
    form.put(route('learningSteps.update', props.step.id), options);
  }
  else {
    form.post(route('learningSteps.store'), options);
  }
}

function cancel(): void {
  router.visit(backHref.value);
}

function destroyStep(): void {
  if (!props.step?.id) {
    return;
  }
  deleteDialogOpen.value = false;
  router.delete(route('learningSteps.destroy', props.step.id));
}
</script>
