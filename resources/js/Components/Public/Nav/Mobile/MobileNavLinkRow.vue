<template>
  <div v-if="link.type === 'divider'" class="my-3 border-t border-zinc-200 dark:border-zinc-700" />

  <div v-else-if="link.type === 'heading'" class="mb-1 mt-4 px-3 text-xs font-bold uppercase tracking-wide text-zinc-400 first:mt-0 dark:text-zinc-500">
    {{ link.name }}
  </div>

  <SmartLink
    v-else
    prefetch
    :href="link.url"
    :target="link.new_tab ? '_blank' : undefined"
    :rel="link.new_tab ? 'noopener' : undefined"
    class="flex min-h-11 items-center gap-3 rounded-md px-3 py-2.5 transition-colors hover:bg-zinc-100 focus:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 dark:hover:bg-zinc-800 dark:focus:bg-zinc-800 dark:focus-visible:ring-zinc-400"
    :class="{ 'mt-3 font-bold': link.type === 'category-link', 'bg-primary/5 dark:bg-primary/10': link.featured }"
    @click="$emit('close')"
  >
    <img v-if="link.image" :src="link.image" alt="" class="size-14 shrink-0 rounded-md object-cover"
      :style="{ objectPosition: link.image_focal ?? '50% 50%' }">
    <Icon v-else-if="link.icon" :icon="`fluent:${link.icon}`" class="size-5 shrink-0 text-zinc-500 dark:text-zinc-400" />

    <div class="min-w-0 flex-1">
      <div class="inline-flex items-center gap-2">
        <span>{{ link.name }}</span>
        <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="size-3.5 opacity-60" />
        <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="rounded-full px-2 py-0 text-[10px]">
          {{ link.small_text }}
        </Badge>
      </div>
      <p v-if="link.description" class="mt-0.5 line-clamp-2 text-sm leading-snug text-zinc-500 dark:text-zinc-400">
        {{ link.description }}
      </p>
    </div>
  </SmartLink>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';

import SmartLink from '../../SmartLink.vue';
import type { NavLink } from '../types';

import { Badge } from '@/Components/ui/badge';

defineProps<{
  link: NavLink;
}>();

defineEmits<{
  close: [];
}>();
</script>
