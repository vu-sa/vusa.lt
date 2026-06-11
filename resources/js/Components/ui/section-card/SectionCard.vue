<template>
  <Card :class="['shadow-none', props.class]">
    <CardHeader :size="headerSize">
      <div class="flex items-center justify-between gap-2">
        <CardTitle class="flex min-w-0 items-center gap-2 text-base">
          <slot name="icon">
            <component :is="icon" v-if="icon" class="h-5 w-5 shrink-0 text-primary" />
          </slot>
          <span class="truncate">{{ title }}</span>
          <span v-if="count !== undefined && count !== null" class="shrink-0 text-sm font-normal text-muted-foreground">
            ({{ count }})
          </span>
        </CardTitle>

        <div v-if="$slots.action || actionLabel" class="flex shrink-0 items-center gap-2">
          <slot name="action">
            <Link
              v-if="actionHref"
              :href="actionHref"
              :class="actionClass"
            >
              {{ actionLabel }}
              <ChevronRight class="h-4 w-4" />
            </Link>
            <button
              v-else
              type="button"
              :class="actionClass"
              @click="$emit('action')"
            >
              {{ actionLabel }}
              <ChevronRight class="h-4 w-4" />
            </button>
          </slot>
        </div>
      </div>
    </CardHeader>

    <CardContent :size="contentSize">
      <slot v-if="empty" name="empty" />
      <slot v-else />
    </CardContent>

    <CardFooter v-if="$slots.footer">
      <slot name="footer" />
    </CardFooter>
  </Card>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronRight } from 'lucide-vue-next';

import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/Components/ui/card';

type CardSize = 'default' | 'compact' | 'sm';

const props = withDefaults(defineProps<{
  title: string;
  icon?: Component;
  count?: number | string | null;
  actionLabel?: string;
  actionHref?: string;
  empty?: boolean;
  headerSize?: CardSize;
  contentSize?: CardSize;
  class?: HTMLAttributes['class'];
}>(), {
  icon: undefined,
  count: undefined,
  actionLabel: undefined,
  actionHref: undefined,
  empty: false,
  headerSize: 'compact',
  contentSize: 'compact',
  class: undefined,
});

defineEmits<{
  action: [];
}>();

const actionClass = [
  'inline-flex shrink-0 items-center gap-1 text-sm text-muted-foreground',
  'transition-colors hover:text-foreground',
  'focus:outline-none focus-visible:text-foreground',
];
</script>
