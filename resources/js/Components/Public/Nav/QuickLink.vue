<template>
  <SmartLink
    v-if="quickLink?.link"
    :href="quickLink.link"
    prefetch
    class="plain inline-flex items-center gap-1.5 whitespace-nowrap text-sm transition-colors duration-200"
    :class="isImportant
      ? 'font-bold text-foreground hover:text-brand'
      : 'font-medium text-muted-foreground hover:text-foreground'"
  >
    <!-- Importance is carried by the *icon's* colour plus the bolder label, not by colouring the
         label itself. A red word among grey ones reads as a warning; a brand-filled mark next to
         a bolder word reads as "this one matters" — and it survives the a11y high-contrast mode,
         which flattens text colours but leaves fills alone.
         translate-y-0 pins the icon: without it the parent's group-hover makes it jump. -->
    <Icon
      v-if="quickLink.icon"
      :icon="`fluent:${quickLink.icon}`"
      class="size-3.5 shrink-0 translate-y-0"
      :class="isImportant && 'text-brand'"
    />
    {{ quickLink.text }}
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';

import SmartLink from '../SmartLink.vue';

const props = defineProps<{
  quickLink: App.Entities.QuickLink | null;
}>();

const isImportant = computed(() => Boolean(props.quickLink?.is_important));
</script>
