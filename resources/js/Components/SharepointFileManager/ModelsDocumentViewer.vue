<template>
  <div
    class="mt-4 rounded-md border border-zinc-200 p-8 shadow-sm dark:border-zinc-50/10"
  >
    <!-- <h3 class="mb-4">Dokumentai</h3> -->
    <div class="h-8">
      <Transition name="fade" mode="out-in">
        <FuzzySearcher
          v-if="!loading"
          key="search"
          :data="loadedDocuments"
          :disabled="loadedDocuments.length === 0"
          @update-results="updateResults"
        />
        <!-- <NSkeleton
          v-else
          key="skeleton"
          height="36px"
          width="384px"
          round
          class="w-96"
        /> -->
      </Transition>
    </div>
    <NDivider />
    <TransitionGroup
      name="list"
      tag="div"
      class="flex max-w-4xl flex-wrap gap-6"
      @after-enter="afterGroupEnter"
    >
      <div v-for="result in results" :key="result.refIndex">
        <ModelDocumentButton
          :loading="loading"
          :document="result.item"
          @file-button-click="$emit('fileButtonClick', result.item)"
        ></ModelDocumentButton>
      </div>
    </TransitionGroup>
  </div>
</template>

<script setup lang="tsx">
import { NDivider, NSkeleton } from "naive-ui";
import { onMounted, ref } from "vue";
import axios from "axios";
import route from "ziggy-js";

import FuzzySearcher from "@/Components/SharepointFileManager/FuzzySearcher.vue";
import ModelDocumentButton from "@/Components/SharepointFileManager/ModelDocumentButton.vue";

defineEmits<{
  (event: "fileButtonClick", document: Record<string, any>): void;
}>();

const props = defineProps<{
  modelCollectionWithDocuments: Array<Record<string, any>>;
}>();

const loading = ref(true);
const absolute = ref(true);

const loadedDocuments = ref([]);

const updateResults = (newResults) => {
  results.value = newResults;
};

const mapDocuments = () => {
  return props.modelCollectionWithDocuments
    .map((model) => {
      return model.documents;
    })
    .flat();
};

const results = ref(mapDocuments());

const getFiles = async () => {
  let documents = mapDocuments();

  let documentIDArray = documents.map((document) => {
    return document.id;
  });

  axios
    .post(route("sharepoint.getFiles"), { documentIds: documentIDArray })
    .then((response) => {
      loadedDocuments.value = response.data;
      results.value = response.data.map((document, index) => {
        return {
          item: document,
          refIndex: index,
        };
      });
      loading.value = false;
    });
};

const afterGroupEnter = () => {
  absolute.value = false;
  console.log(absolute);
};

onMounted(() => {
  getFiles();
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.list-move,
.list-enter-active,
.list-leave-active {
  transition: opacity 0.5s ease-out;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  /* transform: translateX(30px); */
}
.list-leave-active {
  position: v-bind('absolute ? "absolute" : "initial"');
}
</style>
