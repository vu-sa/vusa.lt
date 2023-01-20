<template>
  <div>
    <div class="grid grid-cols-ramFill gap-x-4">
      <DoingCard
        v-for="doing in doings"
        :key="doing.id"
        :doing="doing"
      ></DoingCard>

      <NewGridItemButton
        v-if="matter"
        :icon="AddCircle24Filled"
        @click="showModal = true"
      >
      </NewGridItemButton>
    </div>
    <CardModal
      v-if="matter"
      v-model:show="showModal"
      title="Sukurti veiklą"
      @close="showModal = false"
    >
      <DoingForm :doing="doingTemplate" @submit="handleDoingSubmit" />
    </CardModal>
  </div>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { AddCircle24Filled } from "@vicons/fluent";
import { doingTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingCard from "@/Components/Cards/DoingCard.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import Icons from "@/Types/Icons/filled";
import NewGridItemButton from "../Buttons/NewGridItemButton.vue";

defineProps<{
  doings: App.Entities.Doing[];
  doingTypes: any;
  matter: App.Entities.Matter;
}>();

const showModal = ref(false);

const handleDoingSubmit = (form: any) => {
  form
    .transform((data: any) => ({
      ...data,
      user_id: usePage().props.auth?.user.id,
    }))
    .post(route("doings.store"), {
      onSuccess: () => {
        showModal.value = false;
      },
    });
};
</script>
