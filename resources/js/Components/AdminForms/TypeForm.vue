<template>
  <FormPage
    :title="isCreate ? $t(`types.${typeKind}.create`) : (localizedTitle || $t('Tipas'))"
    :bar-title="isCreate ? $t(`types.${typeKind}.create`) : getTranslatedValue(type.title, undefined, $t('Tipas'))"
    :entity-type
    :back-href="route(`${resource}.index`)"
    :back-label="$t(`types.${typeKind}.title`)"
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
    :activity-subject="!isCreate && form.id ? { type: typeKind, id: String(form.id) } : undefined"
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
          :placeholder="$t(`types.${typeKind}.example`)"
          :class="['h-11', fieldSurfaceClass]"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="description"
        :label="`${$t('forms.fields.description')} (${activeLocale.toUpperCase()})`"
        :error="form.errors[`description.${activeLocale}`]"
      >
        <TiptapEditor v-if="editorReady" :key="activeLocale" v-model="form.description[activeLocale]" tools="description" html />
      </FormFieldWrapper>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('forms.sections.type_parameters')" :icon="SlidersHorizontal" title-class="text-brand">
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
        v-if="typeKind === 'institutionType'"
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
  </FormPage>
</template>

<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { isLoaded, loadLanguageAsync, trans as $t } from 'laravel-vue-i18n';
import { Building2, SlidersHorizontal } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import TiptapEditor from '../TipTap/TiptapEditor.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import FormPanel from '@/Components/Patterns/FormPanel.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { NumberField } from '@/Components/ui/number-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { InstitutionScope, ModelEnum } from '@/Types/enums';

defineEmits<(event: 'submit:form', form: unknown) => void>();

const props = defineProps<{
  typeKind: 'institutionType' | 'dutyType';
  type: (App.Entities.InstitutionType | App.Entities.DutyType);
  contentTypes: Array<{ id: number; title: string | { lt?: string; en?: string } | null }>;
  rememberKey?: string;
}>();

const resource = props.typeKind === 'institutionType' ? 'institutionTypes' : 'dutyTypes';
const entityType = props.typeKind === 'institutionType' ? ModelEnum.INSTITUTION_TYPE : ModelEnum.DUTY_TYPE;

const isCreate = computed(() => Boolean(props.rememberKey));
const activeLocale = ref<'lt' | 'en'>('lt');
const interfaceLocale = String(usePage().props.app.locale);
const editorReady = ref(isLoaded(interfaceLocale));
onMounted(async () => {
  await loadLanguageAsync(interfaceLocale);
  editorReady.value = true;
});

const fieldIds = {
  'title.lt': 'title',
  'title.en': 'title',
  'description.lt': 'description',
  'description.en': 'description',
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

const extraAttributesPeriodicityDays = computed({
  get: () => form.extra_attributes?.meeting_periodicity_days ?? undefined,
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

const localizedTitle = computed(() => getTranslatedValue(form.title));

const missingLocaleCounts = computed(() => ({
  lt: [form.title?.lt].filter(value => !value).length,
  en: [form.title?.en].filter(value => !value).length,
}));

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    type => form.id !== type.id,
  );
});
</script>
