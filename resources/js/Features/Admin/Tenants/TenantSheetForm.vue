<template>
  <SheetForm
    v-model:open="open"
    :title="tenant ? $t('Redaguoti padalinį') : $t('Naujas padalinys')"
    :description="$t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="tenant-fullname">{{ $t('forms.fields.name') }}</Label>
      <Input
        id="tenant-fullname"
        v-model="form.fullname"
        placeholder="Pvz: VU SA Matematikos ir informatikos fakultete"
      />
      <p v-if="form.errors.fullname" class="text-sm text-destructive">
        {{ form.errors.fullname }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="tenant-shortname">{{ $t('forms.fields.slug') }}</Label>
      <Input
        id="tenant-shortname"
        v-model="form.shortname"
        placeholder="Pvz: MIF"
      />
      <p v-if="form.errors.shortname" class="text-sm text-destructive">
        {{ form.errors.shortname }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="tenant-type">{{ $t('forms.fields.type_label') }}</Label>
      <Select v-model="form.type">
        <SelectTrigger id="tenant-type">
          <SelectValue :placeholder="$t('forms.placeholders.select_type')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </SelectItem>
        </SelectContent>
      </Select>
      <p v-if="form.errors.type" class="text-sm text-destructive">
        {{ form.errors.type }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="tenant-alias">
        {{ $t('forms.fields.alias') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <Input
        id="tenant-alias"
        v-model="form.alias"
        placeholder="mif"
      />
      <p v-if="form.errors.alias" class="text-sm text-destructive">
        {{ form.errors.alias }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="tenant-shortname-vu">
        {{ $t('forms.fields.shortname_vu') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <Input
        id="tenant-shortname-vu"
        v-model="form.shortname_vu"
        placeholder="MIF"
      />
      <p v-if="form.errors.shortname_vu" class="text-sm text-destructive">
        {{ form.errors.shortname_vu }}
      </p>
    </div>

    <div v-if="assignableInstitutions && assignableInstitutions.length > 0" class="space-y-2">
      <Label for="tenant-primary-institution">
        {{ $t('forms.fields.primary_institution') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <Select v-model="institutionIdString">
        <SelectTrigger id="tenant-primary-institution">
          <SelectValue :placeholder="$t('forms.placeholders.select_institution')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem value="__none__">
            {{ $t('Nėra') }}
          </SelectItem>
          <SelectItem
            v-for="inst in assignableInstitutions"
            :key="inst.id"
            :value="String(inst.id)"
          >
            {{ inst.name }}
          </SelectItem>
        </SelectContent>
      </Select>
      <p v-if="form.errors.primary_institution_id" class="text-sm text-destructive">
        {{ form.errors.primary_institution_id }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, toRaw, watch } from 'vue';

import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { TenantType } from '@/Types/enums';

export interface TenantInput {
  id?: number | string;
  fullname: string;
  shortname: string;
  type: string;
  alias: string;
  shortname_vu: string;
  primary_institution_id: number | string | null;
}

const props = defineProps<{
  open: boolean;
  tenant?: TenantInput | null;
  assignableInstitutions?: Array<App.Entities.Institution>;
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
  fullname: '',
  shortname: '',
  type: TenantType.Padalinys,
  alias: '',
  shortname_vu: '',
  primary_institution_id: null,
});

const form = useForm<TenantInput>(blank());

const institutionIdString = computed({
  get: () => {
    if (form.primary_institution_id == null) return '__none__';
    return String(form.primary_institution_id);
  },
  set: (val: string) => {
    form.primary_institution_id = val === '__none__' || !val ? null : Number(val);
  },
});

watch(() => props.tenant, (val) => {
  if (val) {
    const raw = structuredClone(toRaw(val));
    form.defaults({
      id: raw.id,
      fullname: raw.fullname ?? '',
      shortname: raw.shortname ?? '',
      type: raw.type ?? TenantType.Padalinys,
      alias: raw.alias ?? '',
      shortname_vu: raw.shortname_vu ?? '',
      primary_institution_id: raw.primary_institution_id ?? null,
    });
  }
  else {
    form.defaults(blank());
  }
  form.reset();
  form.clearErrors();
}, { immediate: true });

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
      emit('saved');
    },
  };

  if (props.tenant?.id) {
    form.patch(route('tenants.update', props.tenant.id), options);
  }
  else {
    form.post(route('tenants.store'), options);
  }
}
</script>
