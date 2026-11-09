<template>
  <FormPage
    :title="pageTitle"
    :bar-title="barTitle"
    :entity-type="ModelEnum.LEARNING_STEP"
    required-note
    :back-href="backHref"
    :back-label="$t('learning.tabs.library')"
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
                class="size-8 shrink-0"
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
                  class="flex w-full items-center justify-between gap-2 border-b border-border px-3 py-2 text-left text-sm last:border-b-0 hover:bg-accent"
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

    <template #aside>
      <FormPanel
        v-if="!isSystem"
        :title="$t('learning_library.sections.who')"
        :icon="Users"
        data-testid="panel-who"
      >
        <FormFieldWrapper
          v-if="!isTenantOnlyCurator"
          id="step-layer"
          :label="$t('learning_library.fields.layer')"
          :hint="$t(`learning_library.layer_hints.${form.layer}`)"
          :error="form.errors.layer"
          required
        >
          <FormSegmentedControl
            v-model="form.layer"
            :options="layerOptions"
            :ariaLabel="$t('learning_library.fields.layer')"
            test-id-prefix="step-layer"
            class="h-auto grid-cols-1 pointer-coarse:h-auto [&>button]:min-h-10 [&>button]:justify-start [&>button]:px-3 pointer-coarse:[&>button]:min-h-11"
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
          :error="form.errors.tenant_id"
          required
        />
      </FormPanel>

      <FormPanel
        :title="$t('learning_library.sections.when')"
        :icon="Clock"
        data-testid="panel-when"
      >
        <FormFieldWrapper
          v-if="isSystem || form.kind !== LearningStepKind.MeetingPrep"
          id="step-stage"
          :label="$t('learning_library.fields.stage')"
          :hint="!isSystem && form.layer === LearningLayer.Tenant ? $t('learning_library.fields.stage_fixed_hint') : undefined"
          :error="form.errors.stage"
          required
        >
          <p
            v-if="!isSystem && form.layer === LearningLayer.Tenant"
            data-testid="stage-fixed-tenant"
            class="text-sm font-medium text-foreground"
          >
            {{ $t('learning_library.fields.stage_fixed_before_start') }}
          </p>
          <FormSegmentedControl
            v-else
            v-model="stageModel"
            :options="stageOptions"
            :ariaLabel="$t('learning_library.fields.stage')"
            test-id-prefix="step-stage"
            class="h-auto grid-cols-1 pointer-coarse:h-auto [&>button]:min-h-10 [&>button]:justify-start [&>button]:px-3 pointer-coarse:[&>button]:min-h-11"
          />
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="!isSystem"
          id="step-importance"
          :label="$t('learning_library.fields.importance')"
          :hint="$t('learning_library.fields.importance_hint')"
          :error="form.errors.importance"
          required
        >
          <FormSegmentedControl
            v-model="form.importance"
            :options="importanceOptions"
            :ariaLabel="$t('learning_library.fields.importance')"
            test-id-prefix="step-importance"
            class="h-auto grid-cols-1 pointer-coarse:h-auto [&>button]:min-h-10 [&>button]:justify-start [&>button]:px-3 pointer-coarse:[&>button]:min-h-11"
          />
        </FormFieldWrapper>
      </FormPanel>

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
import { Clock, Search, SlidersHorizontal, Trash2, Users, X } from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import TenantSelectField from '@/Components/AdminForms/TenantSelectField.vue';
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
  LearningImportance,
  LearningLayer,
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

const activeLocale = ref<LocaleKey>('lt');
const isEdit = computed(() => Boolean(props.step?.id));
const isSystem = computed(() => Boolean(props.step?.is_system));
const backHref = computed(() => (props.step?.id ? route('learningSteps.show', props.step.id) : route('learningSteps.index')));

const allowedLayers = computed<LearningLayer[]>(() => (props.layers.length ? props.layers : ALL_LAYERS));
const isTenantOnlyCurator = computed(
  () => allowedLayers.value.length === 1 && allowedLayers.value[0] === LearningLayer.Tenant,
);
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

const fieldIds: Record<string, string> = {
  'title': 'step-title',
  'title.lt': 'step-title',
  'title.en': 'step-title',
  'description': 'step-description',
  'description.lt': 'step-description',
  'description.en': 'step-description',
  'kind': 'step-kind',
  'layer': 'step-layer',
  'institution_type_id': 'step-institution-type',
  'duty_type_id': 'step-duty-type',
  'tenant_id': 'step-tenant',
  'stage': 'step-stage',
  'importance': 'step-importance',
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

function destroyStep(): void {
  if (!props.step?.id) {
    return;
  }
  deleteDialogOpen.value = false;
  router.delete(route('learningSteps.destroy', props.step.id));
}
</script>
