<template>
  <SmartLink v-if="quickLink?.link" :href="quickLink?.link" prefetch class="plain inline-flex gap-1.5 duration-200" :class="{
    'font-bold text-vusa-red hover:text-vusa-red-secondary': quickLink?.is_important,
    'text-zinc-700 hover:text-vusa-red dark:text-zinc-300': !quickLink?.is_important,
  }">
    <!-- translate-y-0 is needed, because the icon is "jumpy", because of group-hover in parent -->
    <Icon v-if="quickLink.icon" :icon="`fluent:${quickLink.icon}`" class="size-4 translate-y-0" />
    {{ quickLink?.text }}
  </SmartLink>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';

import SmartLink from '../SmartLink.vue';

defineProps<{
  quickLink: App.Entities.QuickLink | null;
}>();
</script>
