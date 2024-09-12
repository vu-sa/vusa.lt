<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
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
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import { RESOURCE_CATEGORY_PLACEHOLDERS } from "@/Constants/I18n/Placeholders";

import FluentIconSelect from "../FormItems/FluentIconSelect.vue";
import FormElement from "./FormElement.vue";
import MdSuspenseWrapper from "@/Features/MarkdownGetterFromDocs/MdSuspenseWrapper.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import AdminForm from "./AdminForm.vue";

const props = defineProps<{
  resourceCategory: any;
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

//const routeToSubmit = computed(() => {
//  return props.resourceCategory?.id
//    ? route(props.modelRoute, props.resourceCategory.id)
//    : route(props.modelRoute);
//});

const form = useForm(props.resourceCategory);

//const submit = () => {
//  // add _method: "patch" if it's an update, to the data of the request
//  // because formdata doesn't support patch, it's needed
//  router.post(
//    routeToSubmit.value,
//    {
//      ...form,
//      _method: props.resourceCategory?.id ? "patch" : "post",
//    },
//    {
//      preserveScroll: true,
//    },
//  );
//};
</script>
