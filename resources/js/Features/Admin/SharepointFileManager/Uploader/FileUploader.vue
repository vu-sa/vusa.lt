<template>
  <CardModal :show :title="`Įkelti naują failą`" @close="$emit('close')">
    <NSteps class="my-2 py-2" :current="(current as number)" :status="'process'">
      <NStep title="Į ką kelsi failą?">
        <template #icon>
          <IFluentDocumentTableSearch24Regular />
        </template>
      </NStep>
      <NStep title="Failo įkėlimas" />
    </NSteps>
    <FadeTransition>
      <FileableForm v-if="current === 1" :show-alert="showAlert" @close:alert="showAlert = false"
        @submit="handleFileableSubmit" />
      <div v-else-if="current === 2">
        <FileForm :fileable="fileForm.fileable" :loading="loading" @submit="handleFileSubmit" />
      </div>
    </FadeTransition>
    <FadeTransition>
      <ModalHelperButton v-if="!showAlert && current === 1" @click="showAlert = true" />
    </FadeTransition>
  </CardModal>
</template>

<script setup lang="ts">
import { inject, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import { useStorage } from "@vueuse/core";

import CardModal from "@/Components/Modals/CardModal.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import FileForm from "./FileForm.vue";
import FileableForm from "@/Components/AdminForms/Special/FileableForm.vue";
import ModalHelperButton from "@/Components/Buttons/ModalHelperButton.vue";

const emit = defineEmits(["close"]);

const props = defineProps<{
  fileable?: Record<string, any>;
  show: boolean; // yes
}>();

const current = ref(1);
const loading = ref(false);
const showAlert = useStorage("new-file-button-alert", true);

const keepFileable = inject<boolean>("keepFileable");

const fileForm = useForm<Record<string, any>>({
  fileable: null,
  file: null,
});

const handleFileableSubmit = (model: App.Entities.SharepointFileable) => {
  fileForm.fileable = model;
  current.value = 2;
};

const handleFileSubmit = (file: any) => {
  fileForm.file = file;
  submitFullForm();
};

const submitFullForm = () => {
  loading.value = true;
  fileForm.post(route("sharepointFiles.store"), {
    onSuccess: () => {
      emit("close");
      if (!keepFileable) {
        fileForm.reset();
        current.value = 1;
      } else {
        current.value = 2;
        fileForm.file = null;
      }
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};

if (props.fileable) {
  handleFileableSubmit(props.fileable);
}
</script>
