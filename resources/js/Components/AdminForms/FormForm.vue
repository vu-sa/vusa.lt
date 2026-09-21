<template>
  <FormPage
    :title="isEditing ? formTitle : $t('Nauja registracijos forma')"
    :head-title="isEditing ? formTitle : $t('Nauja registracijos forma')"
    :lead="isEditing ? undefined : $t('Kurk naują registracijos formą studentams ir nariams.')"
    :entity-type="ModelEnum.FORM"
    :back-href="isEditing && form.id ? route('forms.show', form.id) : route('forms.index')"
    :back-label="isEditing ? $t('Į formą') : $t('Formos')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :field-ids
    :mode="isEditing ? 'edit' : 'create'"
    :locale="activeLocale"
    :available-locales="['lt', 'en']"
    :missing-locale-counts
    @update:locale="activeLocale = $event"
    @submit="emit('submit:form', form)"
  >
    <FormSection
      :title="$t('forms.context.main_info')"
      :description="$t('forms.helpers.form_main_info')"
    >
      <div class="space-y-1.5">
        <Label for="form-name" class="text-sm font-medium">
          {{ $t('forms.fields.name') }} ({{ activeLocale.toUpperCase() }}) *
        </Label>
        <Input
          id="form-name"
          v-model="form.name[activeLocale]"
          :placeholder="$t('forms.fields.name')"
        />
        <p v-if="form.errors[`name.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`name.${activeLocale}`] }}
        </p>
      </div>

      <div class="space-y-2">
        <Label class="text-sm font-medium">
          {{ $t('forms.fields.description') }} ({{ activeLocale.toUpperCase() }})
        </Label>
        <TiptapEditor
          v-if="activeLocale === 'lt'"
          v-model="form.description.lt"
          preset="full"
          html
        />
        <TiptapEditor
          v-else
          v-model="form.description.en"
          preset="full"
          html
        />
        <p v-if="form.errors[`description.${activeLocale}`]" class="text-xs text-destructive">
          {{ form.errors[`description.${activeLocale}`] }}
        </p>
      </div>

      <div class="space-y-3">
        <PermalinkField
          :permalink="form.path.lt"
          :base-url="registrationBaseUrl('lt')"
          label="LT"
          :view-url="publicUrl('lt')"
          @update:permalink="onPathInput('lt', $event)"
        />
        <PermalinkField
          :permalink="form.path.en"
          :base-url="registrationBaseUrl('en')"
          label="EN"
          :view-url="publicUrl('en')"
          @update:permalink="onPathInput('en', $event)"
        />
        <div
          v-if="pathChangedOnExistingForm"
          :class="[
            'flex items-center gap-2 border p-2.5 text-xs font-medium',
            'border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] text-[var(--status-attention)]',
          ]"
        >
          <AlertTriangle class="size-4 shrink-0" />
          <span>{{ $t('Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!') }}</span>
        </div>
      </div>

      <div v-if="assignableTenants && assignableTenants.length > 0" class="space-y-1.5">
        <Label for="form-tenant" class="text-sm font-medium">
          {{ $t('forms.fields.tenant') }}
        </Label>
        <Select v-model="tenantIdString">
          <SelectTrigger id="form-tenant">
            <SelectValue placeholder="VU SA ..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
              {{ tenant.shortname }}
            </SelectItem>
          </SelectContent>
        </Select>
        <p v-if="form.errors.tenant_id" class="text-xs text-destructive">
          {{ form.errors.tenant_id }}
        </p>
      </div>

      <div class="space-y-1.5">
        <Label class="text-sm font-medium">
          {{ $t('forms.fields.form_publish_time') }}
        </Label>
        <DateTimePicker
          v-model="publishTimeDate"
          :placeholder="$t('forms.placeholders.date')"
          @change="onPublishTimeChange"
        />
      </div>
    </FormSection>

    <FormSection
      :title="$t('forms.sections.form_fields')"
      :description="$t('forms.helpers.registrations_count', { count: form.registrations_count ?? 0 })"
    >
      <div v-if="form.registrations_count > 0" class="mb-2">
        <Link :href="route('forms.show', form.id)" class="text-xs font-medium text-primary underline underline-offset-2">
          {{ $t('forms.helpers.view_registrations') }}
        </Link>
      </div>

      <div
        v-if="hasRegistrations"
        class="flex items-center gap-2 border border-border bg-muted/40 p-2.5 text-xs text-muted-foreground"
      >
        <Info class="size-4 shrink-0" />
        <span>{{ $t('Formos laukelių pridėti ar ištrinti nebegalima, nes forma jau turi registracijų.') }}</span>
      </div>

      <div class="flex items-center justify-between pt-1">
        <span class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
          {{ $t('Laukai') }} ({{ form.form_fields.length }})
        </span>
        <Button
          v-if="form.form_fields.length > 1"
          type="button"
          variant="outline"
          size="sm"
          class="u-touch"
          @click="isReordering = !isReordering"
        >
          <ArrowUpDown class="mr-1.5 size-3.5" />
          {{ isReordering ? $t('Baigti tvarkymą') : $t('Keisti tvarką') }}
        </Button>
      </div>

      <SortableFormFieldsTable v-model="form.form_fields" class="mt-2 sortable-table">
        <template #default="{ model }">
          <div class="flex w-full items-center justify-between gap-3 py-1 text-sm text-foreground">
            <div class="flex min-w-0 items-center gap-2.5">
              <span class="text-muted-foreground">
                <Type v-if="model.type === 'string'" class="size-4" />
                <CheckSquare v-else-if="model.type === 'boolean'" class="size-4" />
                <List v-else-if="model.type === 'enum'" class="size-4" />
                <Hash v-else-if="model.type === 'number'" class="size-4" />
                <Calendar v-else-if="model.type === 'date'" class="size-4" />
                <Type v-else class="size-4" />
              </span>

              <span v-if="model.is_required" class="text-destructive font-bold" :title="$t('Privalomas laukas')">*</span>

              <span class="truncate font-medium">
                {{ model.label?.lt || model.label?.en || model.label || '—' }}
              </span>
            </div>

            <div class="flex shrink-0 items-center gap-1">
              <template v-if="isReordering">
                <Button
                  type="button"
                  variant="ghost"
                  size="icon-xs"
                  :disabled="isFirstField(model)"
                  :aria-label="$t('Kelti aukštyn')"
                  @click="moveFieldUp(model)"
                >
                  <ArrowUp class="size-3.5" />
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon-xs"
                  :disabled="isLastField(model)"
                  :aria-label="$t('Nuleisti žemyn')"
                  @click="moveFieldDown(model)"
                >
                  <ArrowDown class="size-3.5" />
                </Button>
              </template>
              <template v-else>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon-xs"
                  :aria-label="$t('Redaguoti')"
                  @click="handleEditFormField(model)"
                >
                  <Pencil class="size-3.5" />
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  size="icon-xs"
                  class="text-destructive hover:text-destructive"
                  :disabled="hasRegistrations"
                  :aria-label="$t('Ištrinti')"
                  @click="handleDeleteFormField(model)"
                >
                  <Trash2 class="size-3.5" />
                </Button>
              </template>
            </div>
          </div>
        </template>
      </SortableFormFieldsTable>

      <div class="mt-4">
        <Button
          type="button"
          variant="outline"
          :disabled="hasRegistrations"
          class="u-touch"
          @click="handleNewFormFieldCreate"
        >
          <Plus class="mr-1.5 size-4" />
          {{ $t('forms.add') }}
        </Button>
      </div>
    </FormSection>

    <template v-if="isEditing && enableDelete" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti formą') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Forma bus perkelta į šiukšlinę; visi jos atsakymai bus išsaugoti.') }}
          </p>
        </div>
        <Button type="button" variant="destructive" size="sm" class="u-touch shrink-0" @click="deleteConfirmOpen = true">
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti formą?')"
        :description="$t('Forma bus perkelta į šiukšlinę.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>

    <CardModal
      v-model:show="showFormFieldModal"
      :title="$t('forms.sections.form_field')"
      @close="showFormFieldModal = false"
    >
      <FormFieldForm
        :field-models="fieldModelOptions"
        :has-registrations
        :form-field="selectedFormField"
        @submit="handleFormFieldSubmitted"
      />
    </CardModal>
  </FormPage>
</template>

<script setup lang="ts">
import { computed, ref, toRaw, watch } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  AlertTriangle,
  ArrowDown,
  ArrowUp,
  ArrowUpDown,
  Calendar,
  CheckSquare,
  Hash,
  Info,
  List,
  Pencil,
  Plus,
  Trash2,
  Type,
} from 'lucide-vue-next';

import FormFieldForm from './FormFieldForm.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import PermalinkField from './PermalinkField.vue';

import CardModal from '@/Components/Dialogs/CardModal.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import SortableFormFieldsTable from '@/Components/Tables/SortableFormFieldsTable.vue';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Button } from '@/Components/ui/button';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { formFieldTemplate } from '@/Types/formTemplates';
import { ModelEnum } from '@/Types/enums';
import { localizedSlug } from '@/Utils/LocalizedRoutes';
import { generateSlug } from '@/Utils/String';

type Locale = 'lt' | 'en';

interface FormFieldItem {
  id: string | number;
  type?: string;
  label?: Record<string, string> | string;
  order?: number;
  is_required?: boolean;
  [key: string]: unknown;
}

const props = defineProps<{
  form: Record<string, unknown>;
  assignableTenants?: Array<App.Entities.Tenant>;
  fieldModelOptions?: Array<{ value: string; label: string }>;
  fieldModelFields?: Array<{ value: string; label: string }>;
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const activeLocale = ref<Locale>('lt');
const isReordering = ref(false);
const deleteConfirmOpen = ref(false);

const showFormFieldModal = ref(false);
const selectedFormField = ref(formFieldTemplate);

// Translatable columns normalisation
const form = useForm({
  ...props.form,
  name: (props.form.name ?? { lt: '', en: '' }) as Record<string, string>,
  description: (props.form.description ?? { lt: '', en: '' }) as Record<string, string>,
  path: (props.form.path ?? { lt: '', en: '' }) as Record<string, string>,
  form_fields: (props.form.form_fields ?? []) as FormFieldItem[],
});

const isEditing = computed(() => Boolean(form.id));
const formTitle = computed(() => form.name?.lt || form.name?.en || '');

const hasRegistrations = computed(() => (Number(form?.registrations_count) || 0) > 0);

const fieldIds = {
  'name.lt': 'form-name',
  'name.en': 'form-name',
  'tenant_id': 'form-tenant',
};

const missingLocaleCounts = computed(() => ({
  lt: (form.name?.lt ?? '').trim() === '' ? 1 : 0,
  en: (form.name?.en ?? '').trim() === '' ? 1 : 0,
}));

const originalPath: Record<Locale, string> = {
  lt: (props.form.path as Record<string, string> | undefined)?.lt ?? '',
  en: (props.form.path as Record<string, string> | undefined)?.en ?? '',
};

const pathTouched = ref<Record<Locale, boolean>>({
  lt: originalPath.lt !== '',
  en: originalPath.en !== '',
});

const pathChangedOnExistingForm = computed(() =>
  (originalPath.lt !== '' && form.path.lt !== originalPath.lt)
  || (originalPath.en !== '' && form.path.en !== originalPath.en),
);

const registrationBaseUrl = (locale: Locale) =>
  `${usePage().props.app.url}/${locale}/${localizedSlug('registrationString', locale)}`;

const publicUrl = (locale: Locale) =>
  form.path[locale] ? `${registrationBaseUrl(locale)}/${form.path[locale]}` : undefined;

const onPathInput = (locale: Locale, value: string) => {
  pathTouched.value[locale] = true;
  form.path[locale] = value;
};

// Keep URL in sync with name until manually edited
(['lt', 'en'] as Locale[]).forEach((locale) => {
  watch(() => form.name?.[locale], (name) => {
    if (pathTouched.value[locale]) {
      return;
    }

    form.path[locale] = generateSlug(String(name || ''));
  });
});

const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const publishTimeDate = ref<Date | null>(
  form.publish_time ? new Date(form.publish_time as string) : null,
);

const onPublishTimeChange = (date: Date | null) => {
  form.publish_time = date ? date.toISOString() : null;
};

function isFirstField(model: FormFieldItem): boolean {
  return form.form_fields.findIndex((f: FormFieldItem) => f.id === model.id) === 0;
}

function isLastField(model: FormFieldItem): boolean {
  return form.form_fields.findIndex((f: FormFieldItem) => f.id === model.id) === form.form_fields.length - 1;
}

function moveFieldUp(model: FormFieldItem): void {
  const index = form.form_fields.findIndex((f: FormFieldItem) => f.id === model.id);
  if (index <= 0) return;
  const current = form.form_fields[index];
  form.form_fields[index] = form.form_fields[index - 1];
  form.form_fields[index - 1] = current;
  updateFieldOrders();
}

function moveFieldDown(model: FormFieldItem): void {
  const index = form.form_fields.findIndex((f: FormFieldItem) => f.id === model.id);
  if (index < 0 || index >= form.form_fields.length - 1) return;
  const current = form.form_fields[index];
  form.form_fields[index] = form.form_fields[index + 1];
  form.form_fields[index + 1] = current;
  updateFieldOrders();
}

function updateFieldOrders(): void {
  form.form_fields.forEach((field: FormFieldItem, idx: number) => {
    field.order = idx + 1;
  });
}

function handleNewFormFieldCreate() {
  selectedFormField.value = structuredClone(formFieldTemplate);
  selectedFormField.value.id = `new-${crypto.randomUUID()}`;
  selectedFormField.value.order = form.form_fields.length + 1;
  showFormFieldModal.value = true;
}

function handleEditFormField(model: unknown) {
  selectedFormField.value = structuredClone(toRaw(model) as typeof formFieldTemplate);
  showFormFieldModal.value = true;
}

function handleDeleteFormField(model: FormFieldItem) {
  const formFieldIndex = form.form_fields.findIndex((field: FormFieldItem) => field.id === model.id);
  if (formFieldIndex !== -1) {
    form.form_fields.splice(formFieldIndex, 1);
    updateFieldOrders();
  }
}

function handleFormFieldSubmitted(formField: unknown) {
  const fieldItem = formField as FormFieldItem;
  const formFieldIndex = form.form_fields.findIndex((field: FormFieldItem) => field.id === fieldItem.id);

  if (formFieldIndex !== -1) {
    form.form_fields[formFieldIndex] = fieldItem;
  }
  else {
    form.form_fields.push(fieldItem);
  }

  updateFieldOrders();
  showFormFieldModal.value = false;
}

defineExpose({
  form,
  showFormFieldModal,
  selectedFormField,
  handleDeleteFormField,
  handleEditFormField,
  handleNewFormFieldCreate,
  onPathInput,
  pathChangedOnExistingForm,
});
</script>
