<template>
  <NTabs class="my-2 rounded-xs" type="segment">
    <NTabPane name="edit" display-directive="show">
      <template #tab>
        <IFluentEdit24Filled class="mr-2" />
        Redagavimas
      </template>
      <RichContentEditor v-model:contents="contentParts" />
    </NTabPane>
    <NTabPane class="bg-zinc-50 p-4 antialiased dark:bg-zinc-900" name="preview">
      <template #tab>
        <IFluentEye24Filled class="mr-2" />
        Peržiūra
      </template>
      <div class="typography flex max-w-prose flex-col gap-2 p-4 text-base leading-7 text-zinc-800 dark:text-zinc-300">
        <RichContentParser :content="contentParts" />
      </div>
    </NTabPane>
  </NTabs>
</template>

<script setup lang="ts">
import RichContentEditor from "./RichContentEditor.vue";
import RichContentParser from "./RichContentParser.vue";

const contentParts = defineModel();
</script>
