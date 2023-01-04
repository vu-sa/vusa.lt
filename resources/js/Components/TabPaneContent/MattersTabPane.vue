<template>
  <div class="grid grid-cols-3 gap-x-4 xl:grid-cols-4">
    <MatterCard
      v-for="matter in matters"
      :key="matter.id"
      :matter="matter"
    ></MatterCard>
    <div
      v-if="institution"
      role="button"
      class="mx-1 my-2 flex h-36 flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon size="40" :depth="5" :component="BookQuestionMark20Filled"></NIcon>
      <span>Sukurti klausimą</span>
    </div>
  </div>
  <CardModal
    v-if="institution"
    v-model:show="showModal"
    title="Sukurti klausimą"
    @close="showModal = false"
  >
    <MatterForm
      :form="matterForm"
      :institution="institution"
      @matter-stored="showModal = false"
    />
  </CardModal>
</template>

<script setup lang="tsx">
import { BookQuestionMark20Filled } from "@vicons/fluent";
import { NIcon } from "naive-ui";
import { ref } from "vue";

import CardModal from "@/Components/Modals/CardModal.vue";
import MatterCard from "@/Components/Cards/MatterCard.vue";
import MatterForm from "@/Components/AdminForms/MatterForm.vue";

defineProps<{
  matters: App.Models.InstitutionMatter[];
  institution: App.Models.Institution;
}>();

const showModal = ref(false);

const matterForm = {
  title: "",
  description: "",
  status: "",
};
</script>
