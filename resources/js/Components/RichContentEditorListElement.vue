<template>
  <section class="flex flex-col gap-5">
    <div class="flex items-center gap-2 text-lg font-bold">
      <NIcon :component="icon" />
      {{ title }} <NTag size="tiny">
        {{ id ? `#${id}` : 'Nauja' }}
      </NTag>
      <div class="ml-auto flex gap-4">
        <NButton type="primary" circle color="#EEEEEE" size="small" bordered @click="$emit('expand')">
          <template #icon>
            <NIcon :component="isExpanded ? ArrowMinimizeVertical24Regular : ArrowMaximizeVertical24Regular" color="#000000" />
          </template>
        </NButton>
        <NButton :disabled="!canDelete" circle type="error" size="small" @click="$emit('remove')">
          <template #icon>
            <NIcon :component="Dismiss24Regular" />
          </template>
        </NButton>
      </div>
    </div>
    <slot />
  </section>
</template>

<script setup lang="ts">
import { ArrowMaximizeVertical24Regular, ArrowMinimizeVertical24Regular, Dismiss24Regular } from '@vicons/fluent';
import { NButton, NIcon, NTag } from 'naive-ui';
import type { Component } from 'vue';

defineProps<{
  icon: Component;
  id?: number;
  title: string;
  canDelete: boolean;
  isExpanded: boolean;
}>()

defineEmits(['expand', 'remove'])
</script>
