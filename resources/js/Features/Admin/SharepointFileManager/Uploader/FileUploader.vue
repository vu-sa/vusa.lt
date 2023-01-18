<template>
  <CardModal :show="show" :title="`Įkelti naują failą`" @close="$emit('close')">
    <NSteps
      class="my-2 py-2"
      :current="(current as number)"
      :status="'process'"
    >
      <NStep title="Į ką kelsi failą?">
        <template #icon>
          <NIcon :component="DocumentTableSearch24Regular"></NIcon>
        </template>
      </NStep>
      <NStep title="Failo įkėlimas"></NStep>
    </NSteps>
    <FadeTransition>
      <FileableForm
        v-if="current === 1"
        :show-alert="showAlert"
        @close:alert="showAlert = false"
        @submit="handleFileableSubmit"
      ></FileableForm>
      <div v-else-if="current === 2">
        <NMessageProvider>
          <FileForm :fileable="fileable"></FileForm>
        </NMessageProvider>
      </div>
    </FadeTransition>
    <FadeTransition>
      <ModalHelperButton
        v-if="!showAlert && current === 1"
        @click="showAlert = true"
      />
    </FadeTransition>
  </CardModal>
</template>

<script setup lang="ts">
import { DocumentTableSearch24Regular } from "@vicons/fluent";
import { NIcon, NMessageProvider, NStep, NSteps } from "naive-ui";
import { ref } from "vue";
import { useStorage } from "@vueuse/core";

import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import FileForm from "./FileForm.vue";
import FileableForm from "@/Components/AdminForms/Special/FileableForm.vue";
import ModalHelperButton from "@/Components/Buttons/ModalHelperButton.vue";

defineEmits(["close", "success"]);

defineProps<{
  show: boolean; // yes
}>();

const current = ref(1);
const showAlert = useStorage("new-file-button-alert", true);

const fileable = ref<App.Entities.Fileable | null>(null);

const handleFileableSubmit = (model: App.Entities.Fileable) => {
  console.log("handleFileableSubmit", model);

  fileable.value = model;
  current.value = 2;
};
</script>
