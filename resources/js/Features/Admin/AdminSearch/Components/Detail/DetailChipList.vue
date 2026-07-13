<template>
  <div v-if="visibleItems.length" class="flex flex-wrap items-center gap-1.5">
    <component
      :is="hrefFor(item) ? Link : 'span'"
      v-for="(item, i) in visibleItems"
      :key="item.id ?? `${item.name}-${i}`"
      :href="hrefFor(item)"
      :class="chipClass"
    >
      <Avatar v-if="avatar && (item.avatarUrl || item.initials)" class="size-5">
        <AvatarImage v-if="item.avatarUrl" :src="item.avatarUrl" :alt="item.name" />
        <AvatarFallback class="text-[9px]">
          {{ item.initials }}
        </AvatarFallback>
      </Avatar>
      {{ item.name }}
    </component>
    <span v-if="hiddenCount > 0" class="text-xs font-medium text-muted-foreground">
      +{{ hiddenCount }}
    </span>
  </div>
  <p v-else-if="emptyMessage" class="text-sm text-muted-foreground">
    {{ emptyMessage }}
  </p>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

export interface DetailChipItem {
  id?: string;
  name: string;
  avatarUrl?: string | null;
  initials?: string;
}

const props = withDefaults(defineProps<{
  items: DetailChipItem[];
  getHref?: (item: DetailChipItem) => string | undefined;
  avatar?: boolean;
  muted?: boolean;
  collapseAt?: number;
  emptyMessage?: string;
}>(), {
  avatar: false,
  muted: false,
});

const visibleItems = computed(() => {
  if (props.collapseAt == null) {
    return props.items;
  }
  return props.items.slice(0, props.collapseAt);
});

const hiddenCount = computed(() => {
  if (props.collapseAt == null) {
    return 0;
  }
  return Math.max(0, props.items.length - props.collapseAt);
});

const hrefFor = (item: DetailChipItem): string | undefined => props.getHref?.(item);

const chipClass = computed(() => {
  const base = props.avatar
    ? 'inline-flex items-center gap-1.5 rounded-full py-1 pl-1 pr-2.5 text-xs'
    : 'inline-flex items-center rounded-full px-2.5 py-1 text-xs';

  return props.muted
    ? `${base} bg-muted/40 text-muted-foreground hover:bg-primary/5 hover:text-foreground`
    : `${base} border bg-muted/40 hover:border-primary/40 hover:bg-primary/5`;
});
</script>
