<template>
  <section class="flex flex-col gap-3">
    <div class="flex items-center gap-2 text-lg font-bold">
      <NIcon :component="icon" />
      {{ title }} <NTag size="tiny">
        {{ id ? `#${id}` : 'Nauja' }}
      </NTag>
      <div class="ml-auto flex gap-4">
        <!-- up and down arrows -->
        <NButton type="primary" circle color="#EEEEEE" size="small" bordered @click="$emit('up')">
          <template #icon>
            <IFluentArrowUp24Regular />
          </template>
        </NButton>
        <NButton type="primary" circle color="#EEEEEE" size="small" bordered @click="$emit('down')">
          <template #icon>
            <IFluentArrowDown24Regular />
          </template>
        </NButton>
        <NButton type="primary" circle color="#EEEEEE" size="small" bordered @click="$emit('expand')">
          <template #icon>
            <IFluentArrowMinimizeVertical24Regular v-if="isExpanded" />
            <IFluentArrowMaximizeVertical24Regular v-else />
          </template>
        </NButton>
        <NButton :disabled="!canDelete" circle type="error" size="small" @click="$emit('remove')">
          <template #icon>
            <IFluentDismiss24Regular />
          </template>
        </NButton>
      </div>
    </div>
    <slot />
  </section>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
  icon: Component;
  id?: number;
  title: string;
  canDelete: boolean;
  isExpanded?: boolean;
}>()

defineEmits(['expand', 'remove'])
</script>
