<template>
  <SheetForm
    v-model:open="open"
    :title="picking ? $t('forms.tenants.pick_institution') : tenant ? $t('Redaguoti padalinį') : $t('Naujas padalinys')"
    :description="picking ? $t('forms.tenants.picker_description') : $t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <template v-if="picking" #body>
      <div class="flex min-h-0 flex-1 flex-col overflow-y-auto p-4">
        <CollectionSelectContent
          :key="tenant?.id"
          collection="institutions"
          :multiple="false"
          :base-filter-by="`tenant_ids:[${tenant?.id}]`"
          :selected-ids="new Set(pendingHit ? [pendingHit.id] : [])"
          :pinned-hits="initialHits"
          :empty-message="$t('forms.tenants.no_institutions')"
          :search-placeholder="$t('Ieškoti institucijos pagal pavadinimą...')"
          @toggle="pendingHit = pendingHit?.id === $event.id ? null : $event"
        />
      </div>
    </template>
    <template v-if="picking" #footer>
      <Button type="button" variant="ghost" @click="closePicker">
        {{ $t('Grįžti') }}
      </Button>
      <Button type="button" variant="outline" data-testid="tenant-picker-clear" @click="pendingHit = null">
        {{ $t('Išvalyti') }}
      </Button>
      <Button type="button" variant="brand" data-testid="tenant-picker-confirm" @click="confirmInstitution">
        {{ $t('Pasirinkti') }}
      </Button>
    </template>

    <p class="text-xs text-muted-foreground">
      {{ $t('forms.required_note') }}
    </p>
    <FormFieldWrapper
      id="tenant-fullname"
      :label="$t('forms.field_model_attributes.fullname')"
      required
      :hint="$t('forms.tenants.fullname_hint')"
      :error="form.errors.fullname"
    >
      <Input id="tenant-fullname" v-model="form.fullname" />
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-shortname"
      :label="$t('forms.fields.short_name')"
      required
      :hint="$t('forms.tenants.shortname_hint')"
      :error="form.errors.shortname"
    >
      <Input id="tenant-shortname" v-model="form.shortname" />
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-type"
      :label="$t('forms.fields.type_label')"
      required
      :hint="$t('forms.tenants.type_hint')"
      :error="form.errors.type"
    >
      <NativeSelect id="tenant-type" v-model="form.type" :options="typeOptions" required :error="form.errors.type" />
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-alias"
      :label="$t('forms.tenants.alias')"
      :hint="$t('forms.tenants.alias_hint')"
      :error="form.errors.alias"
    >
      <Input id="tenant-alias" v-model="form.alias" />
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-shortname-vu"
      :label="$t('forms.fields.shortname_vu')"
      :hint="$t('forms.tenants.shortname_vu_hint')"
      :error="form.errors.shortname_vu"
    >
      <Input id="tenant-shortname-vu" v-model="form.shortname_vu" />
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-primary-institution"
      :label="$t('forms.tenants.primary_institution')"
      :hint="$t('forms.tenants.primary_institution_hint')"
      :error="form.errors.primary_institution_id"
    >
      <SpotlightPopover
        v-if="tenant?.id"
        class="w-full"
        :title="$t('forms.tenants.picker_spotlight_title')"
        :description="$t('forms.tenants.picker_description')"
        :is-dismissed="pickerSpotlight.isDismissed.value"
        @dismiss="pickerSpotlight.dismiss()"
      >
        <Button
          id="tenant-primary-institution"
          type="button"
          variant="outline"
          class="u-touch h-auto w-full justify-between whitespace-normal text-left font-normal"
          @click="startPicking"
        >
          <span class="min-w-0">
            <span class="block">{{ selectedInstitution?.name ?? $t('forms.tenants.pick_institution') }}</span>
            <span v-if="selectedInstitution?.tenant" class="block text-xs text-muted-foreground">{{ selectedInstitution.tenant.shortname }}</span>
          </span>
          <span class="flex shrink-0 items-center gap-2">
            <InactiveInstitutionBadge :active="selectedInstitution?.is_active" />
            <ChevronsUpDown class="size-4 opacity-50" />
          </span>
        </Button>
      </SpotlightPopover>
      <p v-else class="text-sm text-muted-foreground">
        {{ $t('forms.tenants.save_first') }}
      </p>
      <p v-if="crossTenantSelection" role="alert" class="text-sm text-destructive">
        {{ $t('forms.tenants.cross_tenant_warning') }}
      </p>
      <details class="text-sm text-muted-foreground">
        <summary class="u-touch cursor-pointer">
          {{ $t('Plačiau') }}
        </summary>
        <p class="mt-2">
          {{ $t('forms.tenants.primary_institution_details') }}
        </p>
      </details>
    </FormFieldWrapper>
    <FormFieldWrapper
      id="tenant-goals-enabled"
      :label="$t('Įjungti tikslus')"
      :hint="$t('forms.tenants.goals_hint')"
      :error="form.errors.goals_enabled"
    >
      <Checkbox id="tenant-goals-enabled" v-model="form.goals_enabled" />
    </FormFieldWrapper>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronsUpDown } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import InactiveInstitutionBadge from '@/Components/Institutions/InactiveInstitutionBadge.vue';
import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { NativeSelect } from '@/Components/ui/native-select';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import CollectionSelectContent from '@/Features/Admin/AdminSearch/Components/Select/CollectionSelectContent.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { TenantType } from '@/Types/enums';

export interface TenantInstitution {
  id: string;
  name: string;
  is_active: boolean;
  tenant: { id: number; shortname: string } | null;
}

export interface TenantInput {
  id?: number | string;
  fullname: string;
  shortname: string;
  type: string;
  alias: string;
  shortname_vu: string;
  primary_institution_id: string | null;
  goals_enabled: boolean;
}

type TenantWithInstitution = TenantInput & { primary_institution?: TenantInstitution | null };

const props = defineProps<{
  open: boolean;
  tenant?: TenantWithInstitution | null;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
  'saved': [];
}>();

const open = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value),
});

const typeOptions = computed(() => [
  { label: $t('forms.options.tenant_type_padalinys'), value: TenantType.Padalinys },
  { label: $t('forms.options.tenant_type_pkp'), value: TenantType.Pkp },
  { label: $t('forms.options.tenant_type_pagrindinis'), value: TenantType.Pagrindinis },
]);

const blank = (): TenantInput => ({
  fullname: '', shortname: '', type: TenantType.Padalinys, alias: '', shortname_vu: '',
  primary_institution_id: null, goals_enabled: false,
});

const form = useForm<TenantInput>(blank());
const pickerSpotlight = useFeatureSpotlight('tenant-primary-institution-v1');
const picking = ref(false);
const pendingHit = ref<NormalizedSearchHit | null>(null);
const selectedInstitution = ref<TenantInstitution | null>(null);
const crossTenantSelection = computed(() => selectedInstitution.value?.tenant != null
  && String(selectedInstitution.value.tenant.id) !== String(props.tenant?.id));
const initialHits = computed(() => {
  const institution = selectedInstitution.value;
  if (!institution || crossTenantSelection.value) return [];
  return [normalizeHit('institutions', {
    id: institution.id, name_lt: institution.name, name_en: institution.name,
    tenant_shortname: institution.tenant?.shortname, is_active: institution.is_active,
  })];
});

async function startPicking(): Promise<void> {
  pickerSpotlight.dismiss();
  pendingHit.value = initialHits.value[0] ?? null;
  picking.value = true;
  await nextTick();
  document.querySelector<HTMLInputElement>('[data-slot="sheet-form"] input[type="text"]')?.focus();
}

async function confirmInstitution(): Promise<void> {
  const hit = pendingHit.value;
  form.primary_institution_id = hit?.recordId ?? null;
  selectedInstitution.value = hit
    ? {
        id: hit.recordId,
        name: hit.title,
        is_active: !hit.inactiveInstitution,
        tenant: { id: Number(props.tenant?.id), shortname: hit.badge ?? props.tenant?.shortname ?? '' },
      }
    : null;
  await closePicker();
}

async function closePicker(): Promise<void> {
  picking.value = false;
  await nextTick();
  document.getElementById('tenant-primary-institution')?.focus();
}

watch([() => props.tenant, () => props.open], () => {
  const { primary_institution: institution, ...values } = (props.tenant ?? blank()) as TenantWithInstitution;
  form.defaults({ ...blank(), ...values, alias: values.alias ?? '', shortname_vu: values.shortname_vu ?? '' });
  form.reset();
  form.clearErrors();
  selectedInstitution.value = institution ?? null;
  picking.value = false;
  pendingHit.value = null;
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
  picking.value = false;
}

function submit(): void {
  if (picking.value) return;
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };
  if (props.tenant?.id) form.patch(route('tenants.update', props.tenant.id), options);
  else form.post(route('tenants.store'), options);
}
</script>
