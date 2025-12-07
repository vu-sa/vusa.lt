<template>
  <section class="flex flex-col gap-3">
    <div class="flex items-center gap-2 text-lg font-bold">
      <NIcon :component="icon" />
      {{ title }} <NTag size="tiny">
        {{ id ? `#${id}` : 'Nauja' }}
      </NTag>
      <div class="ml-auto flex gap-4">
        <!-- up and down arrows -->
        <Button size="icon-sm" variant="outline" class="rounded-full" @click="$emit('up')">
          <IFluentArrowUp24Regular />
        </Button>
        <Button size="icon-sm" variant="outline" class="rounded-full" @click="$emit('down')">
          <IFluentArrowDown24Regular />
        </Button>
        <Button size="icon-sm" variant="outline" class="rounded-full" @click="$emit('expand')">
          <IFluentArrowMinimizeVertical24Regular v-if="isExpanded" />
          <IFluentArrowMaximizeVertical24Regular v-else />
        </Button>
        <Button :disabled="!canDelete" size="icon-sm" variant="destructive" class="rounded-full" @click="$emit('remove')">
          <IFluentDismiss24Regular />
        </Button>
      </div>
    </div>
    <slot />
  </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';
import { NIcon, NTag } from 'naive-ui';
import { Button } from '@/Components/ui/button';

defineProps<{
  icon: Component;
  id?: number;
  title: string;
  canDelete: boolean;
  isExpanded?: boolean;
}>()

defineEmits(['expand', 'remove', 'up', 'down'])
</script>
