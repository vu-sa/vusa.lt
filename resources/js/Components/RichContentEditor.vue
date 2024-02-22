<template>
  <div class="flex w-full flex-col gap-6">
    <div v-for="content, index in contents" :key="content.id"
      class="relative w-full rounded-md border border-zinc-800 p-6 shadow-md dark:border-zinc-700/40 dark:bg-zinc-800/5">
      <div class="absolute -right-4 -top-4">
        <NButton :disabled="contents?.length < 2" circle type="error" size="small" @click="contents?.splice(index, 1)">
          <template #icon>
            <NIcon :component="Dismiss24Regular" />
          </template>
        </NButton>
      </div>
      <p class="mb-4 text-xl font-bold underline">#{{ index + 1 }}: {{ contentTypes.find((type) => type.value ===
        content.type)?.label }} </p>
      <div v-if="content.type === 'tiptap'">
        <OriginalTipTap v-model="content.json_content" />
      </div>
      <div v-else-if="content.type === 'naiveui-collapse'">
        <NDynamicInput v-model:value="content.json_content" @create="onCreate">
          <template #create-button-default>
            Sukurti
          </template>
          <template #default="{ value }">
            <div
              class="flex w-full flex-col gap-6 rounded-lg border border-zinc-400/80 p-4 dark:border-zinc-800/50 dark:bg-zinc-800/20">
              <NFormItem label="Pavadinimas" :show-feedback="false">
                <NInput v-model:value="value.label" type="text" />
              </NFormItem>
              <OriginalTipTap v-model="value.content" />
            </div>
          </template>
        </NDynamicInput>
      </div>
    </div>
    <div class="my-2 flex max-w-64 gap-2">
      <NSelect v-model:value="selectedNewContent" :options="contentTypes" />
      <NButton type="primary" @click="contents?.push({ json_content: {}, type: selectedNewContent })">Sukurti
        naują
        turinio bloką
      </NButton>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Dismiss24Regular } from '@vicons/fluent';
import { NButton, NDynamicInput, NFormItem, NIcon, NInput, NSelect } from 'naive-ui';
import { ref } from 'vue';

import OriginalTipTap from './TipTap/OriginalTipTap.vue';

defineModel('contents');

function onCreate() {
  return {
    label: "",
    content: {},
  };
}

const selectedNewContent = ref("tiptap");

const contentTypes = [
  {
    value: "tiptap",
    label: "Tekstas",
  },
  {
    value: "naiveui-collapse",
    label: "Akordeonas",
  }
];

</script>
