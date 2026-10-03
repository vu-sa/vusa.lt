<template>
  <FormPage
    :title="isCreate ? $t('Naujas padalinys') : (form.fullname || $t('Padalinys'))"
    :bar-title="isCreate ? undefined : form.fullname"
    :entity-type="ModelEnum.TENANT"
    :back-href="route('tenants.index')"
    :back-label="$t('Padaliniai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :mode="isCreate ? 'create' : 'edit'"
    :available-locales="[]"
    :created-at="tenant?.created_at"
    :updated-at="tenant?.updated_at"
    @submit="emit('submit:form', form)"
  >
    <template v-if="!isCreate" #header-actions>
      <Button as-child variant="outline" size="sm">
        <Link :href="route('tenants.editMainPage', tenant.id)">
          <ExternalLink class="size-4" />
          {{ $t('Redaguoti padalinio pagr. puslapį') }}
        </Link>
      </Button>
    </template>

    <FormSection
      :title="$t('forms.context.main_info')"
      :description="$t('VU SA padaliniai: kiekvienas turi savo svetainės dalį, narius ir pareigybes.')"
    >
      <FormFieldWrapper
        id="fullname"
        :label="$t('forms.fields.name')"
        required
        :error="form.errors.fullname"
      >
        <Input id="fullname" v-model="form.fullname" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="shortname"
        :label="$t('forms.fields.slug')"
        required
        :error="form.errors.shortname"
      >
        <Input id="shortname" v-model="form.shortname" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="shortname_vu"
        :label="`${$t('forms.fields.shortname_vu')} (${$t('neprivaloma')})`"
        :error="form.errors.shortname_vu"
      >
        <Input id="shortname_vu" v-model="form.shortname_vu" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="alias"
        :label="`${$t('forms.fields.alias')} (${$t('neprivaloma')})`"
        :error="form.errors.alias"
      >
        <Input id="alias" v-model="form.alias" />
      </FormFieldWrapper>
    </FormSection>

    <template #aside>
      <FormPanel :title="$t('Tipas ir institucija')" :icon="Building" title-class="text-brand">
        <FormFieldWrapper
          id="type"
          :label="$t('forms.fields.type_label')"
          required
          :error="form.errors.type"
        >
          <Select v-model="form.type">
            <SelectTrigger id="type">
              <SelectValue :placeholder="$t('forms.placeholders.select_type')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                {{ option.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper
          v-if="assignableInstitutions && assignableInstitutions.length > 0"
          id="primary_institution_id"
          :label="`${$t('forms.fields.primary_institution')} (${$t('neprivaloma')})`"
          :error="form.errors.primary_institution_id"
        >
          <Select v-model="institutionIdString">
            <SelectTrigger id="primary_institution_id">
              <SelectValue :placeholder="$t('forms.placeholders.select_institution')" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="__none__">
                {{ $t('Nėra') }}
              </SelectItem>
              <SelectItem
                v-for="institution in assignableInstitutions"
                :key="institution.id"
                :value="String(institution.id)"
              >
                {{ institution.name }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </FormPanel>
    </template>

    <template v-if="enableDelete && !isCreate" #danger-zone>
      <div class="flex items-center justify-between gap-4">
        <div>
          <h4 class="text-sm font-semibold text-destructive">
            {{ $t('Ištrinti padalinį') }}
          </h4>
          <p class="text-xs text-muted-foreground">
            {{ $t('Padalinys bus pašalintas iš sistemos.') }}
          </p>
        </div>
        <Button
          type="button"
          variant="destructive"
          size="sm"
          class="u-touch shrink-0"
          @click="deleteConfirmOpen = true"
        >
          <Trash2 class="mr-1.5 size-4" />
          {{ $t('Ištrinti') }}
        </Button>
      </div>

      <ConfirmDialog
        v-model:open="deleteConfirmOpen"
        :title="$t('Ištrinti padalinį?')"
        :description="$t('Padalinys bus pašalintas iš sistemos.')"
        :confirm-label="$t('Ištrinti')"
        destructive
        @confirm="emit('delete')"
      />
    </template>
  </FormPage>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Building, ExternalLink, Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import FormPage from '@/Components/Layouts/FormPage.vue';
import { ConfirmDialog, FormPanel } from '@/Components/Patterns';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { ModelEnum, TenantType } from '@/Types/enums';

const props = defineProps<{
  tenant: App.Entities.Tenant;
  assignableInstitutions?: Array<App.Entities.Institution>;
  rememberKey?: 'CreateTenant';
  enableDelete?: boolean;
}>();

const emit = defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const isCreate = computed(() => !props.tenant?.id);
const deleteConfirmOpen = ref(false);

const form = props.rememberKey ? useForm(props.rememberKey, props.tenant) : useForm(props.tenant);

const typeOptions = computed(() => [
  { label: $t('forms.options.tenant_type_padalinys'), value: TenantType.Padalinys },
  { label: $t('forms.options.tenant_type_pkp'), value: TenantType.Pkp },
  { label: $t('forms.options.tenant_type_pagrindinis'), value: TenantType.Pagrindinis },
]);

const institutionIdString = computed({
  get: () => {
    if (form.primary_institution_id == null) return '__none__';
    return String(form.primary_institution_id);
  },
  set: (val: string) => {
    form.primary_institution_id = val === '__none__' || !val ? null : Number(val);
  },
});
</script>
