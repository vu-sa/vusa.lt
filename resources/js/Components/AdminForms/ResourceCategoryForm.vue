<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
      <FormElement>
        <template #title>
          {{ $t("forms.context.main_info") }}
        </template>
        <template #description>
          <MdSuspenseWrapper directory="reservations" :locale="$page.props.app.locale" file="main-info" />
        </template>
        <NFormItem :label="$t('forms.fields.title')" required>
          <MultiLocaleInput v-model:input="form.name" :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.name" />
        </NFormItem>
        <NFormItem :label="$t('forms.fields.description')">
          <MultiLocaleInput v-model:input="form.description" input-type="textarea"
            :placeholder="RESOURCE_CATEGORY_PLACEHOLDERS.description" />
        </NFormItem>
        <Suspense>
          <FluentIconSelect :icon="form.icon" @update:icon="(value) => form.icon = value" />
        </Suspense>
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
import { computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";

import { RESOURCE_CATEGORY_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";

import FluentIconSelect from "../FormItems/FluentIconSelect.vue";
import FormElement from "./FormElement.vue";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";

const props = defineProps<{
  resourceCategory: any;
  modelRoute: string;
  deleteModelRoute?: string;
}>();

const routeToSubmit = computed(() => {
  return props.resourceCategory?.id
    ? route(props.modelRoute, props.resourceCategory.id)
    : route(props.modelRoute);
});

const form = useForm(props.resourceCategory);

const submit = () => {
  // add _method: "patch" if it's an update, to the data of the request
  // because formdata doesn't support patch, it's needed
  router.post(
    routeToSubmit.value,
    {
      ...form,
      _method: props.resourceCategory?.id ? "patch" : "post",
    },
    {
      preserveScroll: true,
    },
  );
};
</script>
