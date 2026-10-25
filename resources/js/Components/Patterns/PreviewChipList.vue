<template>
  <ul v-if="items.length" class="flex flex-wrap items-center gap-1.5" data-slot="preview-chip-list">
    <li v-for="(item, index) in visibleItems" :key="item.id ?? `${item.name}-${index}`" class="min-w-0">
      <component
        :is="item.href ? Link : 'span'"
        :href="item.href"
        :class="[
          'inline-flex max-w-full items-center gap-1.5 border border-border px-2 py-1 text-xs text-foreground',
          item.href && 'transition-colors hover:border-brand hover:text-brand pointer-coarse:min-h-11',
        ]"
      >
        <Avatar v-if="item.avatarUrl !== undefined || item.initials" class="size-5 rounded-none">
          <AvatarImage v-if="item.avatarUrl" :src="item.avatarUrl" :alt="item.name" />
          <AvatarFallback class="rounded-none text-[10px]">
            {{ item.initials }}
          </AvatarFallback>
        </Avatar>
        <span class="truncate">{{ item.name }}</span>
        <span v-if="item.meta" class="shrink-0 text-muted-foreground">{{ item.meta }}</span>
      </component>
    </li>
    <li v-if="hiddenCount > 0" class="text-xs font-medium text-muted-foreground" data-slot="preview-chip-more">
      <Link v-if="moreHref" :href="moreHref" class="hover:text-brand">
        +{{ hiddenCount }}
      </Link>
      <template v-else>
        +{{ hiddenCount }}
      </template>
    </li>
  </ul>
  <p v-else-if="emptyMessage" class="text-sm text-muted-foreground">
    {{ emptyMessage }}
  </p>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';

export interface PreviewChipItem {
  id?: string | number;
  name: string;
  href?: string;
  /** A short qualifier after the name, e.g. a unit's short name. */
  meta?: string | null;
  /** `null` still reserves the avatar and shows the initials. */
  avatarUrl?: string | null;
  initials?: string;
}

const props = withDefaults(defineProps<{
  items: PreviewChipItem[];
  /** Long lists collapse to `+N`; a preview is a glance, the record page holds the full list. */
  max?: number;
  /** Where `+N` leads, usually the record page. */
  moreHref?: string;
  emptyMessage?: string;
}>(), {
  max: undefined,
  moreHref: undefined,
  emptyMessage: '',
});

const visibleItems = computed(() => (props.max === undefined ? props.items : props.items.slice(0, props.max)));
const hiddenCount = computed(() => props.items.length - visibleItems.value.length);
</script>
