<template>
  <PageContent :title="training.name.lt" :heading-icon="Icons.CONTACT">
    <UpsertModelLayout>
      <TrainingUpdateForm :training :trainingable-types
        @submit:form="(form) => form.patch(route('trainings.update', form.id), { preserveScroll: true })" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { provide, ref } from "vue";

import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import TrainingUpdateForm from "@/Components/AdminForms/TrainingUpdateForm.vue";

defineProps<{
  training: Array<App.Entities.Training>;
  trainingableTypes: {
    type: string,
    name: string,
    values: { value: string, name: string }[]
  }[];
}>();

//provide("autosave", ref(true));
</script>
