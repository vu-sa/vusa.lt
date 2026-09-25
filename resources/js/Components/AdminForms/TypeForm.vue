<template>
  <FormPage
    :title="isCreate ? $t('Naujas turinio tipas') : (localizedTitle || $t('Tipas'))"
    :bar-title="isCreate ? $t('Naujas turinio tipas') : (localizedTitle || undefined)"
    :entity-type="ModelEnum.TYPE"
    :back-href="route('types.index')"
    :back-label="$t('Tipai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isCreate ? 'create' : 'edit'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    :created-at="!isCreate ? (type?.created_at as string | undefined) : undefined"
    :updated-at="!isCreate ? (type?.updated_at as string | undefined) : undefined"
    :activity-subject="!isCreate && form.id ? { type: 'type', id: String(form.id) } : undefined"
    @update:locale="activeLocale = $event"
    @submit="$emit('submit:form', form)"
  >
    <FormSection :title="$t('forms.context.main_info')" :description="$t('forms.helpers.type_main_info')">
      <FormFieldWrapper
        id="title"
        :label="`${$t('forms.fields.name')} (${activeLocale.toUpperCase()})`"
        required
        :error="form.errors[`title.${activeLocale}`]"
      >
        <Input
          id="title"
          v-model="form.title[activeLocale]"
          :placeholder="activeLocale === 'lt' ? 'Studentų atstovų organas' : 'Student representative body'"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="description"
        :label="`${$t('forms.fields.description')} (${activeLocale.toUpperCase()})`"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <TiptapEditor v-if="activeLocale === 'lt'" v-model="form.description.lt" preset="full" html framed />
        <TiptapEditor v-else v-model="form.description.en" preset="full" html framed />
      </FormFieldWrapper>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('forms.sections.type_parameters')" :icon="SlidersHorizontal" title-class="text-brand">
        <FormFieldWrapper id="model_type" :label="$t('forms.fields.model_type')" required :error="form.errors.model_type">
          <Select v-model="modelTypeString">
            <SelectTrigger id="model_type">
              <SelectValue placeholder="Institucija" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in modelDefaults" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="parent_id" :label="$t('forms.fields.parent_type')" :error="form.errors.parent_id">
          <Select v-model="parentIdString">
            <SelectTrigger id="parent_id">
              <SelectValue placeholder="Studentų atstovybė" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="none">
                {{ $t('Nėra') }}
              </SelectItem>
              <SelectItem v-for="opt in parentTypeOptions" :key="opt.id" :value="String(opt.id)">
                {{ getTranslatedValue(opt.title) }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </FormPanel>

      <FormPanel
        v-if="form.model_type === ModelEnum.INSTITUTION"
        :title="$t('forms.sections.institution_settings')"
        :icon="Building2"
        title-class="text-brand"
      >
        <FormFieldWrapper
          id="governance_scope"
          :label="$t('forms.fields.governance_scope')"
          :hint="$t('forms.helpers.governance_scope_hint')"
        >
          <Select v-model="governanceScope">
            <SelectTrigger id="governance_scope">
              <SelectValue :placeholder="$t('forms.options.governance_scope_inherit')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in governanceScopeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper
          id="meeting_periodicity_days"
          :label="$t('forms.fields.meeting_periodicity_days')"
          :hint="$t('forms.helpers.meeting_periodicity_hint')"
        >
          <NumberField v-model="extraAttributesPeriodicityDays" :min="1" :max="365" />
        </FormFieldWrapper>

        <div class="border border-border">
          <FormToggleRow
            v-model="enableSiblingRelationships"
            :label="$t('forms.fields.enable_sibling_relationships')"
            :hint="$t('forms.helpers.enable_sibling_hint')"
          />
          <FormToggleRow
            v-model="enableCrossTenantSiblingRelationships"
            :label="$t('forms.fields.enable_cross_tenant')"
            :hint="$t('forms.helpers.enable_cross_tenant_hint')"
          />
        </div>
      </FormPanel>
    </template>

    <template #advanced>
      <FormFieldWrapper
        id="slug"
        :label="$t('forms.fields.technical_slug')"
        :hint="$t('forms.helpers.technical_slug_hint')"
        :error="form.errors.slug"
      >
        <Input id="slug" v-model="form.slug" type="text" placeholder="pvz.: turinio-tipas" :class="fieldSurfaceClass" />
      </FormFieldWrapper>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <Button
        variant="outline"
        type="button"
        class="border-destructive/40 text-destructive hover:border-destructive hover:bg-destructive hover:text-destructive-foreground pointer-coarse:min-h-11"
        @click="isDeleteDialogOpen = true"
      >
        <Trash2 class="size-4" />
        {{ $t('Šalinti tipą') }}
      </Button>
    </template>
  </FormPage>

  <ConfirmDialog
    v-model:open="isDeleteDialogOpen"
    :title="$t('Šalinti tipą?')"
    :description="$t('Tipas bus perkeltas į šiukšlinę.')"
    :confirm-label="$t('Šalinti')"
    destructive
    @confirm="$emit('delete')"
  />
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, SlidersHorizontal, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import TiptapEditor from '../TipTap/TiptapEditor.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormToggleRow } from '@/Components/Patterns';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { InstitutionScope, ModelEnum } from '@/Types/enums';
import { modelTypeLabel, modelTypes } from '@/Types/formOptions';

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const props = defineProps<{
  type: App.Entities.Type;
  contentTypes: Array<{ id: string; title: string | { lt?: string; en?: string }; model_type: string }>;
  rememberKey?: 'CreateType';
  enableDelete?: boolean;
}>();

const isCreate = computed(() => props.rememberKey === 'CreateType');
const isDeleteDialogOpen = ref(false);
const activeLocale = ref<'lt' | 'en'>('lt');

const fieldIds = {
  'title.lt': 'title',
  'title.en': 'title',
  'description.lt': 'description',
  'description.en': 'description',
  'model_type': 'model_type',
  'parent_id': 'parent_id',
  'slug': 'slug',
};

interface Translations { lt: string; en: string }

/** `description` may be null or missing a locale; the editor binds `.lt` / `.en` directly. */
const asTranslations = (value: unknown): Translations => ({
  lt: '',
  en: '',
  ...(value && typeof value === 'object' ? value as Partial<Translations> : {}),
});

const initialData = {
  ...props.type,
  title: asTranslations(props.type.title),
  description: asTranslations(props.type.description),
  extra_attributes: props.type.extra_attributes ?? {},
};

const form = props.rememberKey ? useForm(props.rememberKey, initialData) : useForm(initialData);

// Bridge string <-> model for Select
const modelTypeString = computed({
  get: () => form.model_type ?? '',
  set: (val: string) => {
    form.model_type = val || null;
    form.parent_id = null;
  },
});

const parentIdString = computed({
  get: () => form.parent_id != null ? String(form.parent_id) : 'none',
  set: (val: string) => { form.parent_id = val && val !== 'none' ? Number(val) : null; },
});

/** `__inherit__` stands in for a null scope: the type takes its parent's. */
const INHERIT = '__inherit__';

const governanceScopeOptions = [
  { value: INHERIT, label: $t('forms.options.governance_scope_inherit') },
  { value: InstitutionScope.Vusa, label: $t('forms.options.governance_scope_vusa') },
  { value: InstitutionScope.University, label: $t('forms.options.governance_scope_vu') },
  { value: InstitutionScope.National, label: $t('forms.options.governance_scope_national') },
  { value: InstitutionScope.International, label: $t('forms.options.governance_scope_international') },
];

const governanceScope = computed({
  get: () => form.extra_attributes?.governance_scope ?? INHERIT,
  set: (value: string) => {
    form.extra_attributes = {
      ...(form.extra_attributes ?? {}),
      governance_scope: value === INHERIT ? null : value,
    };
  },
});

// Computed property to handle extra_attributes.meeting_periodicity_days
const extraAttributesPeriodicityDays = computed({
  get: () => form.extra_attributes?.meeting_periodicity_days ?? 0,
  set: (value) => {
    if (!form.extra_attributes) {
      form.extra_attributes = {};
    }
    form.extra_attributes = {
      ...form.extra_attributes,
      meeting_periodicity_days: value,
    };
  },
});

// Computed property to handle extra_attributes.enable_sibling_relationships
const enableSiblingRelationships = computed({
  get: () => form.extra_attributes?.enable_sibling_relationships ?? false,
  set: (value) => {
    if (!form.extra_attributes) {
      form.extra_attributes = {};
    }
    form.extra_attributes = {
      ...form.extra_attributes,
      enable_sibling_relationships: value,
    };
  },
});

// Computed property to handle extra_attributes.enable_cross_tenant_sibling_relationships
const enableCrossTenantSiblingRelationships = computed({
  get: () => form.extra_attributes?.enable_cross_tenant_sibling_relationships ?? false,
  set: (value) => {
    if (!form.extra_attributes) {
      form.extra_attributes = {};
    }
    form.extra_attributes = {
      ...form.extra_attributes,
      enable_cross_tenant_sibling_relationships: value,
    };
  },
});

const localizedTitle = computed(() => getTranslatedValue(form.title));

const missingLocaleCounts = computed(() => ({
  lt: [form.title?.lt].filter(value => !value).length,
  en: [form.title?.en].filter(value => !value).length,
}));

const modelDefaults = modelTypes.type.map(alias => ({
  value: alias,
  label: modelTypeLabel(alias),
}));

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    type => form.model_type === type.model_type && form.id !== type.id,
  );
});
</script>
