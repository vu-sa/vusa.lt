<template>
  <!-- TODO: do it without :style -->
  <Card :variant="element.options?.variant"
    :style="`border-color: ${isDark ? cardColors.border.dark[props.element.options?.color] : cardColors.border.light[props.element.options?.color]}`">
    <CardHeader>
      <div class="flex items-center gap-2"
        :style="`color: ${props.element.options?.isTitleColored ? cardColors.title[isDark ? 'dark' : 'light'][props.element.options?.color] : ''}`">
        <component :is="iconToUse" v-if="element.options?.showIcon" class="size-5" />
        <CardTitle class="mb-0 tracking-tight"
          :style="`color: ${props.element.options?.isTitleColored ? cardColors.title[isDark ? 'dark' : 'light'][props.element.options?.color] : ''}`">
          {{ element.options?.title }}
        </CardTitle>
      </div>
    </CardHeader>
    <CardContent class="tracking-normal">
      <slot />
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

import { Card, CardContent, CardHeader, CardTitle } from '../ui/card';

import IconImportant from '~icons/fluent/important24-filled';
import IconInfo from '~icons/fluent/info24-filled';
import IconQuestion from '~icons/fluent/question-circle24-filled';

const props = defineProps<{
  element: models.ContentPart;
}>();

const isDark = useDark();

const iconToUse = computed(() => {
  switch (props.element.options?.color) {
    case 'red':
      return IconImportant;
    case 'yellow':
      return IconQuestion;
    default:
      return IconInfo;
  }
});

const cardColors = {
  border: {
    light: {
      zinc: 'rgb(220, 222, 224)',
      red: 'rgba(189, 40, 53, 0.8)',
      yellow: 'rgb(251, 176, 27)',
    },
    dark: {
      zinc: 'rgba(255, 255, 255, 0.14)',
      red: 'rgba(189, 40, 53, 0.8)',
      yellow: 'rgba(251, 176, 27, 0.8)',
    },
  },
  title: {
    light: {
      zinc: 'rgb(0, 0, 0)',
      red: 'rgb(189, 40, 53)',
      yellow: 'rgb(251, 176, 27)',
    },
    dark: {
      zinc: 'rgb(255, 255, 255)',
      red: 'rgb(189, 40, 53)',
      yellow: 'rgb(251, 176, 27)',
    },
  },
};
</script>
