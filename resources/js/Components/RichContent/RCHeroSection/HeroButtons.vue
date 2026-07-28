<template>
  <div v-if="buttons && buttons.length > 0" :class="['flex flex-col sm:flex-row gap-3 2xl:gap-4', props.class]">
    <template v-for="(button, index) in buttons" :key="index">
      <SmartLink :href="button.link" class="w-fit">
        <Button
          :variant="button.variant || 'default'"
          size="lg"
          class="w-full sm:w-auto"
          :class="getButtonColorClass(button.color || 'red')"
        >
          <RCIcon v-if="button.icon" :name="button.icon" class="mr-2 size-4" />
          {{ button.text }}
        </Button>
      </SmartLink>
    </template>
  </div>
</template>

<script setup lang="ts">
import SmartLink from '@/Components/Public/SmartLink.vue';
import RCIcon from '../RCIcon.vue';
import { Button } from '@/Components/ui/button';
import type { Hero } from '@/Types/contentParts';

const props = defineProps<{
  buttons?: Hero['json_content']['buttons'];
  class?: string;
}>();

function getButtonColorClass(color: string): string {
  const colorClasses = {
    'red': 'bg-vusa-red hover:bg-red-700 text-white border-vusa-red hover:border-red-700',
    'yellow': 'bg-vusa-yellow hover:bg-yellow-500 text-zinc-900 border-vusa-yellow hover:border-yellow-500',
    'zinc': 'bg-zinc-900 dark:bg-zinc-100 hover:bg-zinc-800 dark:hover:bg-zinc-200 text-white dark:text-zinc-900 border-zinc-900 dark:border-zinc-100 hover:border-zinc-800 dark:hover:border-zinc-200',
    'white': 'bg-white dark:bg-zinc-800 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-900 dark:text-zinc-100 border-zinc-200 dark:border-zinc-600 hover:border-zinc-300 dark:hover:border-zinc-500',
  };
  return colorClasses[color as keyof typeof colorClasses] || colorClasses['red'];
}
</script>
