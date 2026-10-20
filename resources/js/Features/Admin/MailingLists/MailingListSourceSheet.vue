<template>
  <SheetForm
    v-model:open="open"
    :title="$t('mailing_lists.sheet.title')"
    :description="$t('mailing_lists.sheet.description')"
    :save-label="$t('mailing_lists.sheet.submit')"
    :processing="form.processing"
    :disabled="!options || !isComplete"
    :dirty="form.isDirty"
    @submit="submit"
  >
    <div v-if="!options" class="space-y-3">
      <p class="text-sm text-muted-foreground">
        {{ $t(loadFailed ? 'mailing_lists.sources.load_failed' : 'Įkeliama…') }}
      </p>
      <Button v-if="loadFailed" variant="outline" voice="sentence" @click="emit('retry')">
        {{ $t('mailing_lists.sources.retry') }}
      </Button>
    </div>
    <div v-else class="space-y-6">
      <p class="text-xs text-muted-foreground">
        {{ $t('mailing_lists.fields.required_note') }}
      </p>
      <FormFieldWrapper id="source_type" :label="$t('mailing_lists.sheet.type')" :error="form.errors.type" required>
        <NativeSelect id="source_type" v-model="form.type" :options="typeOptions" data-testid="source-type" />
      </FormFieldWrapper>

      <DutySearchField
        v-if="form.type === 'duty'" v-model="selectedDuty" :error="form.errors.source_id"
        :tenant-ids="options.globalScope ? undefined : options.tenants.map(tenant => tenant.id)"
      />

      <FormFieldWrapper
        v-else-if="form.type === 'duty_type' || form.type === 'institution_type'"
        id="source_type_target"
        :label="$t('mailing_lists.sheet.target')"
        :error="form.errors.source_id"
        required
      >
        <SingleSelect
          v-if="targetTypes.length > 16"
          v-model="selectedType"
          :options="targetTypes"
          label-field="title"
          value-field="id"
          :placeholder="$t('mailing_lists.sheet.pick')"
          data-testid="source-type-target"
        />
        <NativeSelect
          v-else id="source_type_target" v-model="form.source_id"
          :options="targetTypes.map(type => ({ value: String(type.id), label: type.title }))"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        v-else-if="form.type === 'responsibility'"
        id="source_responsibility"
        :label="$t('mailing_lists.sheet.target')"
        :hint="$t('mailing_lists.sheet.responsibility_hint')"
        :error="form.errors.source_id"
        required
      >
        <SingleSelect
          v-if="options.responsibilities.length > 16"
          v-model="selectedResponsibility"
          :options="options.responsibilities"
          label-field="title"
          value-field="id"
          :placeholder="$t('mailing_lists.sheet.pick')"
          data-testid="source-responsibility"
        />
        <NativeSelect
          v-else id="source_responsibility" v-model="form.source_id"
          :options="options.responsibilities.map(responsibility => ({ value: responsibility.id, label: responsibility.title }))"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        v-else-if="form.type === 'institution'"
        id="source_institution"
        :label="$t('mailing_lists.sheet.target')"
        :error="form.errors.source_id"
        required
      >
        <SingleSelect
          v-if="options.institutions.length > 16"
          v-model="selectedInstitution"
          :options="options.institutions"
          label-field="name"
          value-field="id"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
        <NativeSelect
          v-else id="source_institution" v-model="form.source_id"
          :options="options.institutions.map(institution => ({ value: institution.id, label: institution.name }))"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        v-else-if="form.type === 'mailing_list'"
        id="source_list"
        :label="$t('mailing_lists.sheet.target')"
        :error="form.errors.source_id"
        required
      >
        <SingleSelect
          v-if="options.mailingLists.length > 16"
          v-model="selectedList"
          :options="options.mailingLists"
          label-field="address"
          value-field="id"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
        <NativeSelect
          v-else id="source_list" v-model="form.source_id"
          :options="options.mailingLists.map(list => ({ value: list.id, label: list.address }))"
          :placeholder="$t('mailing_lists.sheet.pick')"
        />
      </FormFieldWrapper>

      <template v-else>
        <FormFieldWrapper id="source_email" :label="$t('mailing_lists.sheet.email')" :error="form.errors.email" required>
          <Input id="source_email" v-model="form.email" type="email" autocomplete="off" data-testid="source-email" />
        </FormFieldWrapper>
        <FormFieldWrapper id="source_display_name" :label="$t('mailing_lists.sheet.display_name')" :error="form.errors.display_name">
          <Input id="source_display_name" v-model="form.display_name" />
        </FormFieldWrapper>
        <FormFieldWrapper id="source_expires_on" :label="$t('mailing_lists.sheet.expires_on')" :error="form.errors.expires_on">
          <DatePicker id="source_expires_on" :model-value="form.expires_on" clearable @update:model-value="setExpiry" />
        </FormFieldWrapper>
      </template>

      <FormFieldWrapper
        v-if="usesAddressMode"
        id="source_address_mode"
        :label="$t('mailing_lists.sheet.address_mode')"
        :hint="$t(`mailing_lists.sources.address_mode_hints.${form.address_mode}`)"
        :error="form.errors.address_mode"
      >
        <FormSegmentedControl
          v-model="form.address_mode"
          :options="addressModeOptions"
          :aria-label="$t('mailing_lists.sheet.address_mode')"
          test-id-prefix="source-address-mode"
        />
      </FormFieldWrapper>

      <template v-if="usesTenantScope">
        <FormFieldWrapper id="source_tenant_scope" :label="$t('mailing_lists.sheet.tenant_scope')" :error="form.errors['filters.tenant_scope']">
          <FormSegmentedControl
            v-model="form.filters.tenant_scope"
            :options="tenantScopeOptions"
            :aria-label="$t('mailing_lists.sheet.tenant_scope')"
            test-id-prefix="source-tenant-scope"
          />
        </FormFieldWrapper>
        <FormFieldWrapper
          v-if="form.filters.tenant_scope === 'selected'"
          id="source_tenants"
          :label="$t('mailing_lists.sources.tenants')"
          :error="form.errors['filters.tenant_ids']"
          required
        >
          <MultiSelect v-model="selectedTenants" :options="options.tenants" label-field="shortname" value-field="id" />
        </FormFieldWrapper>
      </template>

      <div v-if="usesTypeFilters" class="space-y-4 border-t border-border pt-4">
        <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
          {{ $t('mailing_lists.sheet.filters') }}
        </p>
        <label v-if="form.type !== 'institution'" class="flex min-h-11 items-center gap-3 text-sm">
          <Checkbox v-model="form.filters.include_subtypes" />
          {{ $t('mailing_lists.sources.include_subtypes') }}
        </label>
        <FormFieldWrapper v-if="form.type === 'institution_type'" id="source_excluded_types" :label="$t('mailing_lists.sources.excluded_types')">
          <MultiSelect v-model="selectedExcludedTypes" :options="options.institutionTypes" label-field="title" value-field="id" />
        </FormFieldWrapper>
        <FormFieldWrapper v-if="form.type !== 'duty_type'" id="source_duty_types" :label="$t('mailing_lists.sources.duty_types')">
          <MultiSelect v-model="selectedDutyTypes" :options="options.dutyTypes" label-field="title" value-field="id" />
        </FormFieldWrapper>
      </div>

      <FormFieldWrapper id="source_note" :label="$t('mailing_lists.sheet.note')" :hint="$t('mailing_lists.sheet.note_hint')" :error="form.errors.note">
        <Input id="source_note" v-model="form.note" />
      </FormFieldWrapper>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

import type { MailingListSourceOptions } from './types';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { FormSegmentedControl, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { MultiSelect } from '@/Components/ui/multi-select';
import { NativeSelect } from '@/Components/ui/native-select';
import { SingleSelect } from '@/Components/ui/single-select';
import DutySearchField, { type DutyHit } from '@/Features/Admin/Occupancy/DutySearchField.vue';
import { MailingListAddressMode, MailingListSourceType, MailingListTenantScope, TenantType } from '@/Types/enums';

const props = defineProps<{
  mailingListId: string;
  loadFailed?: boolean;
  /** Optional prop: loaded when the sheet opens. */
  options?: MailingListSourceOptions | null;
}>();

const emit = defineEmits<{ retry: [] }>();

const open = defineModel<boolean>('open', { required: true });

const form = useForm({
  type: MailingListSourceType.InstitutionType as MailingListSourceType,
  source_id: '' as string,
  email: '',
  display_name: '',
  note: '',
  expires_on: null as string | null,
  address_mode: MailingListAddressMode.Holder as MailingListAddressMode,
  filters: {
    tenant_scope: MailingListTenantScope.All as MailingListTenantScope,
    tenant_ids: [] as number[],
    include_subtypes: true,
    exclude_institution_type_ids: [] as number[],
    duty_type_ids: [] as number[],
  },
});

const typeOptions = Object.values(MailingListSourceType).map(type => ({ value: type, label: $t(`mailing_lists.sources.types.${type}`) }));

const addressModeOptions: FormSegmentOption<MailingListAddressMode>[] = Object.values(MailingListAddressMode)
  .map(mode => ({ value: mode, label: $t(`mailing_lists.sources.address_modes.${mode}`) }));

const tenantScopeOptions = computed<FormSegmentOption<MailingListTenantScope>[]>(() => Object.values(MailingListTenantScope)
  .filter(scope => scope !== MailingListTenantScope.Main || props.options?.tenants.some(tenant => tenant.type === TenantType.Pagrindinis))
  .filter(scope => scope !== MailingListTenantScope.Padaliniai || props.options?.tenants.some(tenant => tenant.type === TenantType.Padalinys))
  .map(scope => ({ value: scope, label: scope === MailingListTenantScope.All && !props.options?.globalScope
    ? $t('mailing_lists.sources.authorized_tenants')
    : $t(`mailing_lists.sources.tenant_scopes.${scope}`) })));

const usesAddressMode = computed(() => ![MailingListSourceType.Email, MailingListSourceType.MailingList].includes(form.type));
const usesTenantScope = computed(() => [MailingListSourceType.DutyType, MailingListSourceType.InstitutionType, MailingListSourceType.Responsibility].includes(form.type));
const usesTypeFilters = computed(() => [MailingListSourceType.DutyType, MailingListSourceType.Institution, MailingListSourceType.InstitutionType].includes(form.type));

const isComplete = computed(() => (form.type === MailingListSourceType.Email ? form.email.trim() !== '' : Boolean(form.source_id)));

// SingleSelect / MultiSelect work on whole objects; the form stores ids.
const selectedDuty = computed<DutyHit | null>({
  get: () => (form.source_id ? dutyHit : null),
  set: (duty) => {
    dutyHit = duty;
    form.source_id = duty?.id ?? '';
  },
});
let dutyHit: DutyHit | null = null;

const targetTypes = computed(() => (form.type === MailingListSourceType.DutyType ? props.options?.dutyTypes : props.options?.institutionTypes) ?? []);

const selectedType = computed({
  get: () => (form.type === MailingListSourceType.DutyType ? props.options?.dutyTypes : props.options?.institutionTypes)
    ?.find(type => String(type.id) === form.source_id) ?? null,
  set: (type: { id: number } | null) => { form.source_id = type ? String(type.id) : ''; },
});

const selectedInstitution = computed({
  get: () => props.options?.institutions.find(institution => institution.id === form.source_id) ?? null,
  set: (institution: { id: string } | null) => { form.source_id = institution?.id ?? ''; },
});
const selectedResponsibility = computed({
  get: () => props.options?.responsibilities.find(responsibility => responsibility.id === form.source_id) ?? null,
  set: (responsibility: { id: string } | null) => { form.source_id = responsibility?.id ?? ''; },
});
const selectedList = computed({
  get: () => props.options?.mailingLists.find(list => list.id === form.source_id) ?? null,
  set: (list: { id: string } | null) => { form.source_id = list?.id ?? ''; },
});
const selectedTenants = computed({
  get: () => props.options?.tenants.filter(tenant => form.filters.tenant_ids.includes(tenant.id)) ?? [],
  set: (tenants: { id: number }[]) => { form.filters.tenant_ids = tenants.map(tenant => tenant.id); },
});
const selectedExcludedTypes = computed({
  get: () => props.options?.institutionTypes.filter(type => form.filters.exclude_institution_type_ids.includes(type.id)) ?? [],
  set: (types: { id: number }[]) => { form.filters.exclude_institution_type_ids = types.map(type => type.id); },
});
const selectedDutyTypes = computed({
  get: () => props.options?.dutyTypes.filter(type => form.filters.duty_type_ids.includes(type.id)) ?? [],
  set: (types: { id: number }[]) => { form.filters.duty_type_ids = types.map(type => type.id); },
});

watch(() => form.type, () => {
  form.email = '';
  form.display_name = '';
  form.expires_on = null;
  form.source_id = '';
  dutyHit = null;
});

watch(open, (isOpen) => {
  if (isOpen) {
    form.reset();
    form.clearErrors();
  }
});

function setExpiry(date: Date | undefined): void {
  form.expires_on = date
    ? `${date.getUTCFullYear()}-${String(date.getUTCMonth() + 1).padStart(2, '0')}-${String(date.getUTCDate()).padStart(2, '0')}`
    : null;
}

function submit(): void {
  form
    .transform(data => ({
      ...data,
      source_id: data.type === MailingListSourceType.Email ? null : data.source_id,
    }))
    .post(route('mailingLists.sources.store', props.mailingListId), {
      preserveScroll: true,
      except: ['messages'],
      onSuccess: () => {
        form.reset();
        form.defaults();
        open.value = false;
      },
    });
}
</script>
