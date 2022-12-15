<template>
  <div class="my-2 flex max-w-4xl flex-wrap gap-6">
    <template v-for="document in documents" :key="document.id"
      ><ModelDocumentButton
        :loading="loading"
        :document="document"
        @click="$emit('fileButtonClick', document)"
      ></ModelDocumentButton
    ></template>
  </div>
</template>

<script setup lang="tsx">
// Tikslas: turėti ir filtrus, kategorijų ir pavadinimų.
// Iš esmės - self contained susijusių modelių pagal tipą (kolkas) kolekcija.
// Gali būti rodoma Grid, List.

import { onMounted, ref } from "vue";
import ModelDocumentButton from "@/Components/SharepointFileManager/ModelDocumentButton.vue";
import axios from "axios";
import route from "ziggy-js";

defineEmits<{
  (event: "fileButtonClick", document: Record<string, any>): void;
}>();

const props = defineProps<{
  modelCollectionWithDocuments: Array<Record<string, any>>;
}>();

const loading = ref(true);

const documents = ref(
  props.modelCollectionWithDocuments
    .map((model) => {
      return model.documents;
    })
    .flat()
);

// Šiek tiek nesisteminga, kai masyvas išlyginamas 1 lygiu čia, bet
// užklausoje siunčiamas originalus ir backende jis yra išlyginamas irgi...
const documentIDArray = documents.value.map((document) => {
  return document.id;
});

const getFiles = async () => {
  axios
    .post(route("sharepoint.getFiles"), { documentIds: documentIDArray })
    .then((response) => {
      documents.value = response.data;
      loading.value = false;
    });
};

onMounted(() => {
  getFiles();
});
</script>
