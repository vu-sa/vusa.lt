<template>
  <NTabs class="my-2 rounded-sm" type="segment">
    <NTabPane name="edit" :display-directive="'show'">
      <template #tab>
        <NIcon class="mr-2" :component="Edit20Regular" />
        Redagavimas
      </template>
      <RichContentEditor v-model:contents="contentParts" />
    </NTabPane>
    <NTabPane class="bg-zinc-50 p-4 antialiased dark:bg-zinc-900" name="preview">
      <template #tab>
        <NIcon class="mr-2" :component="Eye24Regular" />
        Peržiūra
      </template>
      <div class="prose prose-zinc flex flex-col gap-4 p-4 dark:prose-invert">
        <RichContentParser :content="contentParts" />
      </div>
    </NTabPane>
  </NTabs>
</template>

<script setup lang="ts">
import { Edit20Regular, Eye24Regular } from "@vicons/fluent";
import { NIcon, NTabPane, NTabs } from "naive-ui";

import RichContentEditor from "./RichContentEditor.vue";
import RichContentParser from "./RichContentParser.vue";

const contentParts = defineModel();
</script>
