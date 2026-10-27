<template>
  <!-- Rarely used field groups stay hidden until needed; while empty, each waits here at the form's end. -->
  <ul v-if="actions.length" class="flex flex-wrap items-center gap-2" data-slot="form-fill-actions">
    <li v-for="action in actions" :key="action.key">
      <FillActionButton :label="action.label" :icon="action.icon" :data-testid="`form-fill-${action.key}`" @click="action.run()" />
    </li>
  </ul>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

import FillActionButton from './FillActionButton.vue';

export interface FormFillAction {
  key: string;
  label: string;
  icon?: Component;
  run: () => void;
}

defineProps<{ actions: FormFillAction[] }>();
</script>
