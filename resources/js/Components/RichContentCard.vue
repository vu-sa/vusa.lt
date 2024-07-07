<template>
  <!-- TODO: do it without :style -->
  <Card :variant="element.options?.variant"
    :style="`border-color: ${isDark ? cardColors.border.dark[props.element.options?.color] : cardColors.border.light[props.element.options?.color]}`">
    <CardHeader>
      <div class="flex items-center gap-2"
        :style="`color: ${props.element.options?.isTitleColored ? cardColors.title[isDark ? 'dark' : 'light'][props.element.options?.color] : ''}`">
        <NIcon v-if="element.options?.showIcon" size="20" :component="iconToUse" />
        <CardTitle class="mb-0"
          :style="`color: ${props.element.options?.isTitleColored ? cardColors.title[isDark ? 'dark' : 'light'][props.element.options?.color] : ''}`">
          {{ element.options?.title }}
        </CardTitle>
      </div>
    </CardHeader>
    <CardContent>
      <slot />
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from './ShadcnVue/ui/card';
import { type CardProps } from 'naive-ui';
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

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
    }
  },
  background: {
    light: {
      zinc: 'rgb(247, 247, 247)',
      red: 'rgba(189, 40, 53, 0.1)',
      yellow: 'rgba(251, 176, 27, 0.1)',
    },
    dark: {
      zinc: 'rgba(255, 255, 255, 0.06)',
      red: 'rgba(189, 40, 53, 0.1)',
      yellow: 'rgba(251, 176, 27, 0.06)',
    }
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
    }
  }
};

const commonOverrides = {
  borderRadius: '10px',
};

const lightCardThemeOverrides = computed<NonNullable<CardProps['themeOverrides']>>(() => {
  return {
    ...commonOverrides,
    borderColor: props.element.options?.variant === 'outline' ? cardColors.border.light[props.element.options?.color] : 'rgb(220, 222, 224)',
    color: props.element.options?.variant !== 'outline' ? cardColors.background.light[props.element.options?.color] : undefined,
    titleTextColor: props.element.options?.isTitleColored ? cardColors.title.light[props.element.options?.color] : undefined
  };
});

const darkCardThemeOverrides = computed<NonNullable<CardProps['themeOverrides']>>(() => {
  return {
    ...commonOverrides,
    borderColor: props.element.options?.variant === 'outline' ? cardColors.border.dark[props.element.options?.color] : 'rgba(255, 255, 255, 0.14)',
    color: props.element.options?.variant !== 'outline' ? cardColors.background.dark[props.element.options?.color] : undefined,
    titleTextColor: props.element.options?.isTitleColored ? cardColors.title.dark[props.element.options?.color] : undefined
  };
});
</script>
