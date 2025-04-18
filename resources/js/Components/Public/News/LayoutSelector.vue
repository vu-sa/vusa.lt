<template>
  <div class="flex items-center space-x-2">
    <div class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $t('Vaizdas') }}:</div>
    <div class="inline-flex rounded-md border border-zinc-200 bg-white p-1 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
      <button
        v-for="option in layoutOptions"
        :key="option.value"
        @click="$emit('update:layout', option.value)"
        :class="[
          'inline-flex h-8 w-8 items-center justify-center rounded-sm transition-colors',
          modelValue === option.value 
            ? 'bg-red-600 text-white' 
            : 'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-700'
        ]"
        :title="option.title"
      >
        <component :is="option.icon" class="h-4 w-4" />
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { h } from 'vue';

import IFluentTextWrap24Regular from "~icons/fluent/text-wrap-24-regular";
import IFluentSparkle24Regular from "~icons/fluent/sparkle-24-regular";
import IFluentTextColumn24Regular from "~icons/fluent/text-column-one-24-regular";

const props = defineProps<{
  modelValue: 'modern' | 'classic' | 'immersive'
}>();

defineEmits<{
  (e: 'update:layout', layout: 'modern' | 'classic' | 'immersive'): void
}>();

// Define layout options
const layoutOptions = [
  {
    value: 'modern',
    icon: IFluentTextWrap24Regular,
    title: $t('Modernus vaizdas')
  },
  {
    value: 'classic',
    icon: IFluentTextColumn24Regular,
    title: $t('Klasikinis vaizdas')
  },
  {
    value: 'immersive',
    icon: IFluentSparkle24Regular,
    title: $t('Vizualus vaizdas')
  }
];
</script>