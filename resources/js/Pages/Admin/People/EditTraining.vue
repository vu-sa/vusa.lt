<template>
  <PageContent :title="training.name.lt" :heading-icon="Icons.CONTACT">
    <UpsertModelLayout>
      <TrainingUpdateForm :training :trainable-types
        @submit:form="(form) => form.patch(route('trainings.update', form.id), { preserveScroll: true })" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { provide, ref } from 'vue';

import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import TrainingUpdateForm, { type TrainableTypes } from '@/Components/AdminForms/TrainingUpdateForm.vue';

defineProps<{
  training: App.Entities.Training;
  trainableTypes: TrainableTypes;
}>();

// provide("autosave", ref(true));
</script>
