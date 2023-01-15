<template>
  <div class="grid grid-cols-3 gap-x-4 xl:grid-cols-4">
    <DoingCard
      v-for="doing in doings"
      :key="doing.id"
      :doing="doing"
    ></DoingCard>
    <div
      v-if="matter"
      role="button"
      class="mx-1 my-2 flex flex-col items-center justify-center gap-1 rounded-lg border border-dashed border-zinc-700 p-2 text-zinc-500 duration-200 hover:shadow-inner dark:bg-zinc-900/60"
      @click="showModal = true"
    >
      <NIcon size="24" :depth="5" :component="Icons.GOAL"></NIcon>
      <span>Sukurti veiklą?</span>
    </div>
  </div>
  <CardModal
    v-if="matter"
    v-model:show="showModal"
    title="Sukurti veiklą"
    @close="showModal = false"
  >
    <DoingForm :doing="doingTemplate" @submit="handleDoingSubmit" />
  </CardModal>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import { doingTemplate } from "@/Types/formTemplates";
import CardModal from "@/Components/Modals/CardModal.vue";
import DoingCard from "@/Components/Cards/DoingCard.vue";
import DoingForm from "@/Components/AdminForms/DoingForm.vue";
import Icons from "@/Types/Icons/filled";

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
