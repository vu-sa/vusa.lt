<template>
  <div class="grid grid-cols-ramFill gap-4">
    <MatterCard
      v-for="matter in matters"
      :key="matter.id"
      class="max-w-md"
      :matter="matter"
    ></MatterCard>
    <NewGridItemButton
      v-if="institution"
      :icon="AddCircle24Filled"
      @click="showModal = true"
    >
    </NewGridItemButton>
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
import NewGridItemButton from "../Buttons/NewGridItemButton.vue";

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
