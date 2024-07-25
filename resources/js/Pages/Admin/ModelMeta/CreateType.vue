<template>
  <PageContent title="Naujas turinio tipas" :back-url="route('types.index')">
    <UpsertModelLayout>
      <TypeForm :content-types :roles :type @submit:form="handleSubmit" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { typeTemplate as type } from "@/Types/formTemplates";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contentTypes: Record<string, any>[];
  roles?: App.Entities.Role[];
}>();

const handleSubmit = (form: any) => {
  form.post(route("types.store"));
};
</script>
