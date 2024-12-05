<template>
  <PageContent :title="training.name.lt" :heading-icon="Icons.CONTACT">
    <UpsertModelLayout>
      <TrainingUpdateForm :training
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
}>();

provide("autosave", ref(true));
</script>
