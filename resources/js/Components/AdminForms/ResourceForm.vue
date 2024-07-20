<template>
  <NForm :model="form" label-placement="top" :disabled="formDisabled">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <NFormItem :label="$t('forms.fields.title')" required>
          <MultiLocaleInput v-model:input="form.name" :placeholder="RESOURCE_PLACEHOLDERS.title" />
        </NFormItem>
        <NFormItem :label="$t('forms.fields.description')" required>
          <MultiLocaleInput v-model:input="form.description" input-type="textarea"
            :placeholder="RESOURCE_PLACEHOLDERS.description" />
        </NFormItem>
        <NFormItem label="Identifikacinis kodas (nebūtinas)">
          <NInput v-model:value="form.identifier" placeholder="PRJ-CB-01-K" />
        </NFormItem>
        <NFormItem :label="capitalize($tChoice('entities.tenant.model', 1))" required>
          <NSelect v-model:value="form.tenant_id" :options="assignableTenants" label-field="shortname" value-field="id"
            placeholder="VU SA X" clearable />
        </NFormItem>
      </FormElement>
      <FormElement :icon="Icons.IMAGE">
        <template #title>
          {{ $t("forms.fields.media") }}
        </template>
        <template #description>
          <component :is="RESOURCE_DESCRIPTIONS.media[$page.props.app.locale]" />
        </template>
        <NUpload ref="upload" :file-list="form.media" accept="image/jpg, image/jpeg, image/png" list-type="image-card"
          multiple @change="handleChange">
          {{ $t("forms.context.upload_media") }}
        </NUpload>
      </FormElement>
      <FormElement>
        <template #title>
          {{ $t("forms.context.additional_info") }}
        </template>
        <NFormItem :label="$t('forms.fields.location')" required>
          <NInput v-model:value="form.location" placeholder="Naugarduko g. X (VU P), 010 kab." />
        </NFormItem>
        <NFormItem :label="$t('forms.fields.quantity')" required>
          <NInputNumber v-model:value="form.capacity" :min="1" type="number" />
        </NFormItem>
        <NFormItem :label="capitalize($t('entities.reservation.is_reservable'))" required>
          <NSwitch v-model:value="form.is_reservable" :checked-value="1" :unchecked-value="0" />
        </NFormItem>
        <NFormItem label="Kategorija">
          <NSelect v-model:value="form.resource_category_id" :render-label="renderTag" :options="categoriesOptions"
            placeholder="Kategorija" clearable />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <!-- <DeleteModelButton
        v-if="deleteModelRoute"
        :form="form"
        :model-route="deleteModelRoute"
      /> -->
      <!-- <UpsertModelButton :form="form" :model-route="modelRoute" /> -->
      <NButton type="primary" @click="submit">
        {{ $t("forms.submit") }}
      </NButton>
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { Icon } from '@iconify/vue';
import {
  type UploadFileInfo,
} from "naive-ui";
import { capitalize, computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";

import { RESOURCE_DESCRIPTIONS } from "@/Constants/I18n/Descriptions";
import { RESOURCE_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";
import type { ResourceCreationTemplate } from "@/Pages/Admin/Reservations/CreateResource.vue";
import type { ResourceEditType } from "@/Pages/Admin/Reservations/EditResource.vue";

const props = defineProps<{
  resource: ResourceCreationTemplate | ResourceEditType;
  categories: App.Entities.ResourceCategory[];
  assignableTenants: App.Entities.Tenant[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const categoriesOptions = computed(() => {
  return props.categories.map((category) => ({
    value: category.id,
    label: category.name,
    icon: category.icon,
  }));
});

const routeToSubmit = computed(() => {
  return props.resource?.id
    ? route(props.modelRoute, props.resource.id)
    : route(props.modelRoute);
});

const form = useForm(props.resource);

// tenant_id is set to 0 if it's not found. Shouldn't happen for authenticated users.
const formDisabled = computed(() => {
  return form.tenant_id === 0;
});

const renderTag = (category) => {
  console.log(category);
  return <span class="inline-flex items-center gap-2"><Icon icon={`fluent:${category.icon}`} />{category.label}</span>;
}

const submit = () => {
  // add _method: "patch" if it's an update, to the data of the request
  // because formdata doesn't support patch, it's needed
  router.post(
    routeToSubmit.value,
    {
      ...form,
      _method: props.resource?.id ? "patch" : "post",
    },
    {
      preserveScroll: true,
    },
  );
};

const handleChange = ({ fileList }: { fileList: Array<UploadFileInfo> }) => {
  form.media = fileList;
};
</script>
