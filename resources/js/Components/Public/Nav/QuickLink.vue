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
    <!-- Pin the icon against SmartLink's group-hover transform. -->
    <Icon
      v-if="quickLink.icon"
      :icon="iconData ?? `fluent:${quickLink.icon}`"
      :ssr="Boolean(iconData)"
      class="size-3.5 shrink-0 translate-y-0"
      :class="isImportant && 'text-brand'"
    />
    {{ quickLink.text }}
  </SmartLink>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { usePage } from '@inertiajs/vue3';

import SmartLink from '../SmartLink.vue';

const props = defineProps<{
  quickLink: App.Entities.QuickLink | null;
}>();

const isImportant = computed(() => Boolean(props.quickLink?.is_important));
const page = usePage();
const iconData = computed(() => props.quickLink?.icon
  ? page.props.publicAssets?.icons?.[props.quickLink.icon]
  : undefined);
</script>
