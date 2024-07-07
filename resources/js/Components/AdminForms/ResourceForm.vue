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
        <NFormItem :label="capitalize($tChoice('entities.padalinys.model', 1))" required>
          <NSelect v-model:value="form.padalinys_id" :options="padaliniai" label-field="shortname" value-field="id"
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

<script setup lang="ts">
import {
  NButton,
  NForm,
  NFormItem,
  NInput,
  NInputNumber,
  NSelect,
  NSwitch,
  NUpload,
  type UploadFileInfo,
} from "naive-ui";
import { capitalize, computed, ref } from "vue";
import { router, useForm, usePage } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/regular";
import MultiLocaleInput from "../FormElements/MultiLocaleInput.vue";
// import UpsertModelButton from "@/Components/Buttons/UpsertModelButton.vue";
import { RESOURCE_DESCRIPTIONS } from "@/Constants/I18n/Descriptions";
import { RESOURCE_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";
import type { ResourceCreationTemplate } from "@/Pages/Admin/Reservations/CreateResource.vue";
import type { ResourceEditType } from "@/Pages/Admin/Reservations/EditResource.vue";

const props = defineProps<{
  resource: ResourceCreationTemplate | ResourceEditType;
  padaliniai: App.Entities.Padalinys[];
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const routeToSubmit = computed(() => {
  return props.resource?.id
    ? route(props.modelRoute, props.resource.id)
    : route(props.modelRoute);
});

const form = useForm(props.resource);

// padalinys_id is set to 0 if it's not found. Shouldn't happen for authenticated users.
const formDisabled = computed(() => {
  return form.padalinys_id === 0;
});

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
