<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" :placeholder="RESOURCE_PLACEHOLDERS.title" />
      </FormFieldWrapper>
      <FormFieldWrapper id="description" :label="$t('forms.fields.description')" required :error="form.errors.description">
        <MultiLocaleInput v-model:input="form.description" input-type="textarea"
          :placeholder="RESOURCE_PLACEHOLDERS.description" />
      </FormFieldWrapper>
      <FormFieldWrapper id="identifier" label="Identifikacinis kodas (nebūtinas)" :error="form.errors.identifier">
        <Input id="identifier" v-model="form.identifier" placeholder="PRJ-CB-01-K" />
      </FormFieldWrapper>
      <FormFieldWrapper id="tenant_id" :label="capitalize($tChoice('entities.tenant.model', 1))" required :error="form.errors.tenant_id">
        <Select v-model="tenantIdString">
          <SelectTrigger>
            <SelectValue placeholder="VU SA X" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
              {{ tenant.shortname }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
    <FormElement :icon="Icons.IMAGE">
      <template #title>
        {{ $t("forms.fields.media") }}
      </template>
      <template #description>
        <MdSuspenseWrapper directory="resources" :locale="$page.props.app.locale" file="description" />
      </template>
      <ImageUpload v-model:files="mediaFiles" :max="10" mode="deferred" folder="resources"
        accept="image/jpg,image/jpeg,image/png" />
    </FormElement>
    <FormElement>
      <template #title>
        {{ $t("forms.context.additional_info") }}
      </template>
      <FormFieldWrapper id="location" :label="$t('forms.fields.location')" required :error="form.errors.location">
        <Input id="location" v-model="form.location" placeholder="Naugarduko g. X (VU P), 010 kab." />
      </FormFieldWrapper>
      <FormFieldWrapper id="capacity" :label="$t('forms.fields.quantity')" required :error="form.errors.capacity">
        <NumberField id="capacity" v-model="form.capacity" :min="1" />
      </FormFieldWrapper>
      <FormFieldWrapper id="is_reservable" :label="capitalize($t('entities.reservation.is_reservable'))" required :error="form.errors.is_reservable">
        <Switch :checked="form.is_reservable === 1" @update:checked="(val: boolean) => form.is_reservable = val ? 1 : 0" />
      </FormFieldWrapper>
      <FormFieldWrapper id="resource_category_id" label="Kategorija" :error="form.errors.resource_category_id">
        <Select v-model="categoryIdString">
          <SelectTrigger>
            <SelectValue placeholder="Kategorija" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="category in categoriesOptions" :key="category.value" :value="String(category.value)">
              <span class="inline-flex items-center gap-2">
                <Icon :icon="`fluent:${category.icon}`" />
                {{ category.label }}
              </span>
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { capitalize, computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t, transChoice as $tChoice } from "laravel-vue-i18n";

import { Input } from "@/Components/ui/input";
import { NumberField } from "@/Components/ui/number-field";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { ImageUpload } from "@/Components/ui/upload";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import AdminForm from "./AdminForm.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import { RESOURCE_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";
import type { ResourceCreationTemplate } from "@/Pages/Admin/Reservations/CreateResource.vue";
import type { ResourceEditType } from "@/Pages/Admin/Reservations/EditResource.vue";
import MdSuspenseWrapper from '@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue';

const props = defineProps<{
  resource: ResourceCreationTemplate | ResourceEditType;
  categories: App.Entities.ResourceCategory[];
  assignableTenants: App.Entities.Tenant[];
  rememberKey?: "CreateResource";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const categoriesOptions = computed(() => {
  return props.categories.map((category) => ({
    value: category.id,
    label: category.name,
    icon: category.icon,
  }));
});

const form = props.rememberKey ? useForm(props.rememberKey, props.resource) : useForm(props.resource);

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

const categoryIdString = computed({
  get: () => form.resource_category_id != null ? String(form.resource_category_id) : '',
  set: (val: string) => { form.resource_category_id = val ? Number(val) : null; },
});

// Deferred upload files
const mediaFiles = ref<File[]>([]);
</script>
