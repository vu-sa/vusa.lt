<template>
  <FadeTransition>
    <div class="mt-4 inline-flex w-fit max-w-4xl flex-wrap gap-4">
      <div v-if="loading" class="mt-4 flex flex-col gap-2">
        <NSkeleton :height="148" :width="192"></NSkeleton>
        <NSkeleton :repeat="2" :height="8" :width="168"></NSkeleton>
      </div>
      <FileButton
        v-else
        :document="document"
        @file-button-click="$emit('fileButtonClick')"
      ></FileButton>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { NSkeleton } from "naive-ui";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import FileButton from "@/Components/SharepointFileManager/FileButton.vue";

defineEmits(["fileButtonClick"]);

const props = defineProps<{
  document: Record<string, unknown>[];
  loading: boolean;
}>();
</script>
