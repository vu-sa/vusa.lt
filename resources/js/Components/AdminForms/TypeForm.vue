<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pagrindinė informacija apie turinio tipą.
      </template>
      <FormFieldWrapper id="title" label="Pavadinimas" required>
        <MultiLocaleInput v-model:input="form.title"
          :placeholder="{ lt: 'Studentų atstovų organas', en: 'Student representative body' }" />
      </FormFieldWrapper>

      <div class="space-y-2">
        <div class="flex items-center gap-2">
          <Label>Aprašymas</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Tipo parametrai
      </template>
      <template #description>
        Parametrai
      </template>
      <FormFieldWrapper id="model_type" label="Modelio tipas" required>
        <Select v-model="modelTypeString">
          <SelectTrigger>
            <SelectValue placeholder="Institucija" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="opt in modelDefaults" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper id="parent_id" label="Tėvinis tipas">
        <Select v-model="parentIdString">
          <SelectTrigger>
            <SelectValue placeholder="Studentų atstovybė" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="none">{{ $t('Nėra') }}</SelectItem>
            <SelectItem v-for="opt in parentTypeOptions" :key="opt.id" :value="String(opt.id)">
              {{ opt.title }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
    <FormElement v-if="sharepointPath">
      <template #title>
        Failai
      </template>
      <template #description>
        Failai, susiję su šiuo tipu. Šie failai rodomi atitinkamose vietose
        prie modelių, kurie priklauso šiam tipui.
      </template>
      <FileManager :starting-path="sharepointPath" :fileable="{ id: form.id, type: 'Type' }" />
    </FormElement>
    <FormElement>
      <template #title>
        Tipą turintys modeliai
      </template>
      <template #description>
        Modeliai, kurie priklauso šiam tipui.
      </template>
      <div class="col-span-6">
        <Label class="mb-2">Modeliai</Label>
        <TransferList v-model="form[props.modelType]" :options="modelOptions ?? []">
          <template #source-label="{ option }">
            <span class="inline-flex items-center gap-2">
              {{ option.label }} ({{ option.model?.tenants?.[0]?.shortname ?? option.model?.tenants?.shortname }})
              <a target="_blank" :href="route(`${props.modelType}.edit`, option.value)">
                <Button variant="ghost" size="icon-xs" @click.stop>
                  <Edit16Filled class="ml-2 align-middle" />
                </Button>
              </a>
            </span>
          </template>
        </TransferList>
      </div>
    </FormElement>
    <FormElement v-if="form.model_type === 'App\\Models\\Duty'">
      <template #title>
        Rolės, kurios priskiriamos pareigybėms
      </template>
      <template #description>
        Šios rolės automatiškai priskiriamos pareigybėms su šiuo
        tipu.
      </template>
      <div class="col-span-6">
        <Label class="mb-2">Rolės</Label>
        <TransferList v-model="form.roles" :options="roles?.map((role) => ({
          value: role.id,
          label: role.name,
        })) ?? []" />
      </div>
    </FormElement>
    <FormElement v-if="form.model_type === 'App\\Models\\Institution'">
      <template #title>
        Institucijos nustatymai
      </template>
      <template #description>
        Nustatymai, taikomi visoms šio tipo institucijoms.
      </template>
      <FormFieldWrapper id="meeting_periodicity_days" label="Susitikimų periodiškumas (dienomis)"
        hint="Rekomenduojamas laikotarpis tarp susitikimų. Jei nenurodyta, naudojamas 30 dienų numatymas.">
        <NumberField v-model="extraAttributesPeriodicityDays" :min="1" :max="365" />
      </FormFieldWrapper>
      <FormFieldWrapper id="enable_sibling_relationships" label="Rodyti susijusias institucijas pagal tipą"
        hint="Įjungus, institucijos su šiuo tipu tame pačiame padalinyje bus matomos kaip susijusios viena kitai (pvz., atstovavimo skydelyje).">
        <Switch :model-value="enableSiblingRelationships" @update:model-value="enableSiblingRelationships = $event" />
      </FormFieldWrapper>
      <FormFieldWrapper id="enable_cross_tenant" label="Rodyti padalinių institucijas iš pagrindinio"
        hint="Įjungus, pagrindinio padalinio institucija su šiuo tipu matys visas kitų padalinių institucijas su tuo pačiu tipu. Vienkryptis: padalinių institucijos nematys pagrindinės.">
        <Switch :model-value="enableCrossTenantSiblingRelationships" @update:model-value="enableCrossTenantSiblingRelationships = $event" />
      </FormFieldWrapper>
    </FormElement>
    <FormElement no-divider>
      <template #title>
        Kiti nustatymai
      </template>
      <FormFieldWrapper id="slug" label="Techninė žymė"
        hint="Keičiama tik išskirtiniais atvejais.">
        <Input v-model="form.slug" type="text" placeholder="pvz.: turinio-tipas" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";

import Edit16Filled from "~icons/fluent/edit16-filled"

import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { NumberField } from "@/Components/ui/number-field";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { TransferList } from "@/Components/ui/transfer-list";
import FileManager from "@/Features/Admin/SharepointFileManager/SharepointFileManager.vue";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TiptapEditor from "../TipTap/TiptapEditor.vue";
import AdminForm from "./AdminForm.vue";
import { modelTypes } from "@/Types/formOptions";

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const props = defineProps<{
  type: App.Entities.Type;
  modelType?: string;
  contentTypes: Record<string, any>[];
  sharepointPath?: string;
  allModelsFromModelType?: Record<string, any>[];
  roles?: App.Entities.Role[];
  rememberKey?: "CreateType";
}>();

const locale = ref("lt");

const form = props.rememberKey
  ? useForm(props.rememberKey, {
      ...props.type,
      extra_attributes: props.type.extra_attributes ?? {},
    })
  : useForm({
      ...props.type,
      extra_attributes: props.type.extra_attributes ?? {},
    });

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

// map e.g. form.institutions to id only, so it's used in transfer values

if (props.modelType) {
  form[props.modelType] = props.type[props.modelType]?.map((model) => model.id);
}

const modelDefaults = modelTypes.type.map((type) => {
  return {
    value: "App\\Models\\" + type,
    label: type,
  };
});

const modelOptions = computed(() => {
  return props.allModelsFromModelType?.map((model) => {
    return {
      value: model.id,
      label: model.title ?? model.name,
      model: model,
    };
  });
});

const parentTypeOptions = computed(() => {
  return props.contentTypes.filter(
    (type) => form.model_type === type.model_type && form.id !== type.id,
  );
});
</script>
