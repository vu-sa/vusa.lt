<template>
  <NCard style="width: clamp(300px, 100%, 540px)"
    :theme-overrides="isDark ? darkCardThemeOverrides : lightCardThemeOverrides"
    :bordered="element.options?.variant === 'outline'">
    <template #header>
      <div class="flex items-center gap-2">
        <NIcon v-if="element.options?.showIcon" :component="iconToUse" />
        <span>{{ element.options?.title }}</span>
      </div>
    </template>
    <slot />
  </NCard>
</template>

<script setup lang="ts">
import { type CardProps, NCard, NIcon } from 'naive-ui';
import { Important24Filled, Info24Filled, QuestionCircle24Filled } from '@vicons/fluent';
import { computed } from 'vue';
import { useDark } from '@vueuse/core';

const props = defineProps<{
  element: App.Models.ContentPart;
}>();

const isDark = useDark();

const iconToUse = computed(() => {
  switch (props.element.options?.color) {
    case 'red':
      return Important24Filled;
    case 'yellow':
      return QuestionCircle24Filled;
    default:
      return Info24Filled;
  }
});

const cardColors = {
  border: {
    light: {
      zinc: 'rgb(220, 222, 224)',
      red: 'rgba(189, 40, 53, 0.7)',
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
