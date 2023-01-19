<template>
  <div class="grid grid-cols-ramFill gap-4">
    <MatterCard
      v-for="matter in matters"
      :key="matter.id"
      class="max-w-md"
      :matter="matter"
    ></MatterCard>
    <button
      v-if="institution"
      class="flex max-w-sm flex-col items-center justify-center gap-2 rounded-sm border px-2 py-4 text-zinc-500 duration-200 hover:shadow-inner dark:border-zinc-700 dark:shadow-zinc-900"
      @click="showModal = true"
    >
      <NIcon size="40" :depth="5" :component="AddCircle24Filled"></NIcon>
      <span class="text-center">Sukurti naują?</span>
    </button>
    <CardModal
      v-model:show="showModal"
      title="Sukurti klausimą"
      @close="showModal = false"
    >
      <MatterForm
        :form="matterTemplate"
        :institution="institution"
        @submit="handleSubmit"
      />
    </CardModal>
  </div>
</template>

<script setup lang="tsx">
import { AddCircle24Filled, BookQuestionMark24Filled } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { ref } from "vue";

import { matterTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import MatterCard from "@/Components/Cards/MatterCard.vue";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";

const props = defineProps<{
  matters: App.Entities.Matter[];
  institution: App.Entities.Institution;
}>();

const showModal = ref(false);

const handleSubmit = (form: any) => {
  form
    .transform((data: any) => {
      return {
        ...data,
        institution_id: props.institution.id,
      };
    })
    .post(route("matters.store"), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
};
</script>
