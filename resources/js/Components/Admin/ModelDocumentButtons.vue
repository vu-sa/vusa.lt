<template>
  <FadeTransition>
    <div class="mt-4 inline-flex w-fit max-w-4xl flex-wrap gap-4">
      <template v-if="loading">
        <div
          v-for="document in documents"
          :key="document.id"
          class="mt-4 flex flex-col gap-2"
        >
          <NSkeleton :height="148" :width="192"></NSkeleton>
          <NSkeleton :repeat="2" :height="8" :width="168"></NSkeleton>
        </div>
      </template>
      <template v-else>
        <FileButton
          v-for="document in sharepointFiles"
          :key="document.id"
          :document="document"
          @click="$emit('fileButtonClick', document)"
        ></FileButton>
      </template>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { NSkeleton } from "naive-ui";
import { onMounted, ref } from "vue";
import axios from "axios";
import route from "ziggy-js";

import FadeTransition from "../Public/Utils/FadeTransition.vue";
import FileButton from "@/Components/Admin/Buttons/FileButton.vue";

defineEmits<{
  (event: "fileButtonClick", document: Record<string, any>): void;
}>();

const props = defineProps<{
  model: Record<string, unknown>;
  documents?: Record<string, unknown>[];
}>();

const loading = ref(true);

const sharepointFiles = ref([]);

const getFiles = async () => {
  axios.post(route("sharepoint.getFiles"), props.model).then((response) => {
    sharepointFiles.value = response.data;
    loading.value = false;
  });
};

onMounted(() => {
  getFiles();
});
</script>
