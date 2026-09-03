<template>
  <div v-if="link.type === 'divider'" class="my-3 border-t border-border" />

  <div v-else-if="link.type === 'heading'" class="u-eyebrow px-4 pb-1 pt-4 first:pt-3">
    {{ link.name }}
  </div>

  <SmartLink
    v-else
    prefetch
    :href="link.url"
    :target="link.new_tab ? '_blank' : undefined"
    :rel="link.new_tab ? 'noopener' : undefined"
class="flex min-h-11 items-center gap-3 border-t border-border/50 px-4 py-3 transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
    :class="link.type === 'category-link'
      ? 'u-eyebrow border-t-0 pb-1 pt-4'
      : 'text-sm hover:text-brand'"
    @click="$emit('close')"
  >
    <img v-if="link.image" :src="link.image" alt="" class="size-14 shrink-0 object-cover"
      :style="{ objectPosition: link.image_focal ?? '50% 50%' }">
    <Icon v-else-if="link.icon" :icon="`fluent:${link.icon}`" class="size-5 shrink-0 text-muted-foreground" />

    <div class="min-w-0 flex-1">
      <div class="inline-flex items-center gap-2">
        <span :class="link.type !== 'category-link' && 'font-bold text-foreground'">{{ link.name }}</span>
        <Icon v-if="link.new_tab" icon="fluent:open-16-regular" class="size-3.5 opacity-60" />
        <Badge v-if="link.small_text" :variant="link.badge_variant ?? 'rose'" class="px-2 py-0 text-[10px]">
          {{ link.small_text }}
        </Badge>
      </div>
      <p v-if="link.description" class="mt-0.5 line-clamp-2 text-xs leading-snug text-muted-foreground">
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
